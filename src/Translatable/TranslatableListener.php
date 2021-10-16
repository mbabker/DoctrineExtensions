<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * The translation listener handles the generation and
 * loading of translations for Translatable objects.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless, the annotation metadata is properly cached, and
 * it is not a big overhead to look up all object annotations since
 * the caching is activated for metadata.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableListener extends MappedEventSubscriber
{
    /**
     * Query hint to override the fallback of translations
     * integer 1 for true, 0 false
     */
    public const HINT_FALLBACK = 'gedmo.translatable.fallback';

    /**
     * Query hint to override the fallback locale
     */
    public const HINT_TRANSLATABLE_LOCALE = 'gedmo.translatable.locale';

    /**
     * Query hint to use inner join strategy for translations
     */
    public const HINT_INNER_JOIN = 'gedmo.translatable.inner_join.translations';

    /**
     * Locale which is set on this listener.
     *
     * If the object being translated has a locale defined it
     * will override this one
     *
     * @var string
     */
    protected $locale = 'en_US';

    /**
     * Default locale
     *
     * This changes behavior to not update the original record
     * field if the locale which is used for updating is not default.
     * This will load the default translation in other locales
     * if the record is not translated yet.
     *
     * @var string
     */
    private $defaultLocale = 'en_US';

    /**
     * If this is set to false, when an object does not have
     * a translation for the requested locale, it will show a blank value.
     *
     * @var bool
     */
    private $translationFallback = false;

    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     *
     * @var array<string, object[]>
     */
    private $pendingTranslationInserts = [];

    /**
     * Currently in case if there is TranslationQueryWalker
     * in charge. We need to skip issuing additional queries
     * on load
     *
     * @var bool
     */
    private $skipOnLoad = false;

    /**
     * Tracks the locale the objects currently translated in
     *
     * @var array
     */
    private $translatedInLocale = [];

    /**
     * Flag tracking whether to persist the default locale
     * translation or keep it in the original record.
     *
     * @var bool
     */
    private $persistDefaultLocaleTranslation = false;

    /**
     * Tracks translation objects for the default locale
     *
     * @var array<string, array<string, object>>
     */
    private $translationInDefaultLocale = [];

    /**
     * Specifies the list of events to listen on.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'postLoad',
            'postPersist',
            'preFlush',
            'onFlush',
            'loadClassMetadata',
        ];
    }

    /**
     * Set whether the onLoad event should be skipped
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function setSkipOnLoad($bool)
    {
        $this->skipOnLoad = (bool) $bool;

        return $this;
    }

    /**
     * Set whether to persist the default locale translation or
     * keep it in the original record
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function setPersistDefaultLocaleTranslation($bool)
    {
        $this->persistDefaultLocaleTranslation = (bool) $bool;

        return $this;
    }

    /**
     * Check whether to persist the default locale translation or
     * keep it in the original record
     *
     * @return bool
     */
    public function getPersistDefaultLocaleTranslation()
    {
        return (bool) $this->persistDefaultLocaleTranslation;
    }

    /**
     * Add a translation for the pending object ID which is being inserted
     *
     * @param string $oid
     * @param object $translation
     */
    public function addPendingTranslationInsert($oid, $translation)
    {
        $this->pendingTranslationInserts[$oid][] = $translation;
    }

    /**
     * Maps additional metadata for the object.
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Get the translation class to be used for the object
     *
     * @param class-string $class
     *
     * @return class-string
     */
    public function getTranslationClass(TranslatableAdapter $ea, $class)
    {
        return isset(self::$configurations[$this->name][$class]['translationClass']) ?
            self::$configurations[$this->name][$class]['translationClass'] :
            $ea->getDefaultTranslationClass()
        ;
    }

    /**
     * Set whether translation fallbacks are active for the
     * original record value.
     *
     * @param bool $bool
     *
     * @return $this
     */
    public function setTranslationFallback($bool)
    {
        $this->translationFallback = (bool) $bool;

        return $this;
    }

    /**
     * Check whether translation fallbacks are active for the
     * original record value.
     *
     * @return bool
     */
    public function getTranslationFallback()
    {
        return $this->translationFallback;
    }

    /**
     * Set the locale to use for the translation listener
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setTranslatableLocale($locale)
    {
        $this->validateLocale($locale);
        $this->locale = $locale;

        return $this;
    }

    /**
     * Sets the default locale.
     *
     * This changes behavior to not update the original record
     * field if the locale which is used for updating is not default.
     *
     * @param string $locale
     *
     * @return $this
     */
    public function setDefaultLocale($locale)
    {
        $this->validateLocale($locale);
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * Gets the default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Get the locale in use on the translation listener
     *
     * @return string
     */
    public function getListenerLocale()
    {
        return $this->locale;
    }

    /**
     * Gets the locale to use for translation. Loads the object
     * defined locale first.
     *
     * @param object             $object
     * @param ClassMetadata      $meta
     * @param ObjectManager|null $om
     *
     * @return string
     *
     * @throws \Gedmo\Exception\RuntimeException if the language or locale property is not found on the object
     */
    public function getTranslatableLocale($object, $meta, $om = null)
    {
        $locale = $this->locale;
        if (isset(self::$configurations[$this->name][$meta->name]['locale'])) {
            $class = $meta->getReflectionClass();
            $reflectionProperty = $class->getProperty(self::$configurations[$this->name][$meta->name]['locale']);
            if (!$reflectionProperty) {
                $column = self::$configurations[$this->name][$meta->name]['locale'];
                throw new \Gedmo\Exception\RuntimeException("There is no locale or language property ({$column}) found on object: {$meta->name}");
            }
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($object);
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = (string) $value;
            }
            if ($this->isValidLocale($value)) {
                $locale = $value;
            }
        } elseif ($om instanceof DocumentManager) {
            list($mapping, $parentObject) = $om->getUnitOfWork()->getParentAssociation($object);
            if (null != $parentObject) {
                $parentMeta = $om->getClassMetadata(get_class($parentObject));
                $locale = $this->getTranslatableLocale($parentObject, $parentMeta, $om);
            }
        }

        return $locale;
    }

    /**
     * Handle translation changes in the default locale
     *
     * This has to be done in the preFlush because when an object has been loaded
     * in a different locale, no changes will be detected.
     */
    public function preFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($this->translationInDefaultLocale as $oid => $fields) {
            $trans = reset($fields);
            if ($ea->usesPersonalTranslation(get_class($trans))) {
                $entity = $trans->getObject();
            } else {
                $entity = $uow->tryGetById($trans->getForeignKey(), $trans->getObjectClass());
            }

            if (!$entity) {
                continue;
            }

            try {
                $uow->scheduleForUpdate($entity);
            } catch (ORMInvalidArgumentException $e) {
                foreach ($fields as $field => $trans) {
                    $this->removeTranslationInDefaultLocale($oid, $field);
                }
            }
        }
    }

    /**
     * Looks for translatable objects being inserted or updated for further processing
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        // check all scheduled inserts for Translatable objects
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, false);
            }
        }
        // check scheduled deletions for Translatable entities
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $wrapped = AbstractWrapper::wrap($object, $om);
                $transClass = $this->getTranslationClass($ea, $meta->name);
                $ea->removeAssociatedTranslations($wrapped, $transClass, $config['useObjectClass']);
            }
        }
    }

    /**
     * Checks for inserted objects to update their translation foreign keys
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        // check if entity is tracked by translatable and without foreign key
        if ($this->getConfiguration($om, $meta->name) && count($this->pendingTranslationInserts)) {
            $oid = spl_object_hash($object);
            if (array_key_exists($oid, $this->pendingTranslationInserts)) {
                // load the pending translations without key
                $wrapped = AbstractWrapper::wrap($object, $om);
                $objectId = $wrapped->getIdentifier();
                $translationClass = $this->getTranslationClass($ea, get_class($object));
                foreach ($this->pendingTranslationInserts[$oid] as $translation) {
                    if ($ea->usesPersonalTranslation($translationClass)) {
                        $translation->setObject($objectId);
                    } else {
                        $translation->setForeignKey($objectId);
                    }
                    $ea->insertTranslationRecord($translation);
                }
                unset($this->pendingTranslationInserts[$oid]);
            }
        }
    }

    /**
     * After an object is loaded, the listener updates the translations
     * by the currently used locale
     */
    public function postLoad(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);
        if (isset($config['fields'])) {
            $locale = $this->getTranslatableLocale($object, $meta, $om);
            $oid = spl_object_hash($object);
            $this->translatedInLocale[$oid] = $locale;
        }

        if ($this->skipOnLoad) {
            return;
        }

        if (isset($config['fields']) && ($locale !== $this->defaultLocale || $this->persistDefaultLocaleTranslation)) {
            // fetch translations
            $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
            $result = $ea->loadTranslations(
                $object,
                $translationClass,
                $locale,
                $config['useObjectClass']
            );
            // translate object's translatable properties
            foreach ($config['fields'] as $field) {
                $translated = null;
                foreach ($result as $entry) {
                    if ($entry['field'] == $field) {
                        $translated = isset($entry['content']) ? $entry['content'] : null;
                        break;
                    }
                }
                // update translation
                if (null !== $translated
                    || (!$this->translationFallback && (!isset($config['fallback'][$field]) || !$config['fallback'][$field]))
                    || ($this->translationFallback && isset($config['fallback'][$field]) && !$config['fallback'][$field])
                ) {
                    $ea->setTranslationValue($object, $field, $translated);
                    // ensure clean changeset
                    $ea->setOriginalObjectProperty(
                        $om->getUnitOfWork(),
                        $oid,
                        $field,
                        $meta->getReflectionProperty($field)->getValue($object)
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Validates the given locale
     *
     * @param string $locale locale to validate
     *
     * @throws \Gedmo\Exception\InvalidArgumentException if locale is not valid
     */
    protected function validateLocale($locale)
    {
        if (!$this->isValidLocale($locale)) {
            throw new \Gedmo\Exception\InvalidArgumentException('Locale or language cannot be empty and must be set through Listener or Entity');
        }
    }

    /**
     * Check if the given locale is valid
     *
     * @param string $locale locale to check
     *
     * @return bool
     */
    private function isValidlocale($locale)
    {
        return is_string($locale) && strlen($locale);
    }

    /**
     * Creates the translation for object being flushed
     *
     * @param object $object
     * @param bool   $isInsert
     *
     * @throws \UnexpectedValueException if locale is not valid, or primary key is composite, missing or invalid
     */
    private function handleTranslatableObjectUpdate(TranslatableAdapter $ea, $object, $isInsert)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->name);
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
        $translationMetadata = $om->getClassMetadata($translationClass);

        // check for the availability of the primary key
        $objectId = $wrapped->getIdentifier();
        // load the currently used locale
        $locale = $this->getTranslatableLocale($object, $meta, $om);

        $uow = $om->getUnitOfWork();
        $oid = spl_object_hash($object);
        $changeSet = $ea->getObjectChangeSet($uow, $object);
        $translatableFields = $config['fields'];
        foreach ($translatableFields as $field) {
            $wasPersistedSeparetely = false;
            $skip = isset($this->translatedInLocale[$oid]) && $locale === $this->translatedInLocale[$oid];
            $skip = $skip && !isset($changeSet[$field]) && !$this->getTranslationInDefaultLocale($oid, $field);
            if ($skip) {
                continue; // locale is same and nothing changed
            }
            $translation = null;
            foreach ($ea->getScheduledObjectInsertions($uow) as $trans) {
                if ($locale !== $this->defaultLocale
                    && get_class($trans) === $translationClass
                    && $trans->getLocale() === $this->defaultLocale
                    && $trans->getField() === $field
                    && $this->belongsToObject($ea, $trans, $object)) {
                    $this->setTranslationInDefaultLocale($oid, $field, $trans);
                    break;
                }
            }

            // lookup persisted translations
            foreach ($ea->getScheduledObjectInsertions($uow) as $trans) {
                if (get_class($trans) !== $translationClass
                    || $trans->getLocale() !== $locale
                    || $trans->getField() !== $field) {
                    continue;
                }

                if ($ea->usesPersonalTranslation($translationClass)) {
                    $wasPersistedSeparetely = $trans->getObject() === $object;
                } else {
                    $wasPersistedSeparetely = $trans->getObjectClass() === $config['useObjectClass']
                        && $trans->getForeignKey() === $objectId;
                }

                if ($wasPersistedSeparetely) {
                    $translation = $trans;
                    break;
                }
            }

            // check if translation already is created
            if (!$isInsert && !$translation) {
                $translation = $ea->findTranslation(
                    $wrapped,
                    $locale,
                    $field,
                    $translationClass,
                    $config['useObjectClass']
                );
            }

            // create new translation if translation not already created and locale is different from default locale, otherwise, we have the date in the original record
            $persistNewTranslation = !$translation
                && ($locale !== $this->defaultLocale || $this->persistDefaultLocaleTranslation)
            ;
            if ($persistNewTranslation) {
                $translation = $translationMetadata->newInstance();
                $translation->setLocale($locale);
                $translation->setField($field);
                if ($ea->usesPersonalTranslation($translationClass)) {
                    $translation->setObject($object);
                } else {
                    $translation->setObjectClass($config['useObjectClass']);
                    $translation->setForeignKey($objectId);
                }
            }

            if ($translation) {
                // set the translated field, take value using reflection
                $content = $ea->getTranslationValue($object, $field);
                $translation->setContent($content);
                // check if need to update in database
                $transWrapper = AbstractWrapper::wrap($translation, $om);
                if (((is_null($content) && !$isInsert) || is_bool($content) || is_int($content) || is_string($content) || !empty($content)) && ($isInsert || !$transWrapper->getIdentifier() || isset($changeSet[$field]))) {
                    if ($isInsert && !$objectId && !$ea->usesPersonalTranslation($translationClass)) {
                        // if we do not have the primary key yet available
                        // keep this translation in memory to insert it later with foreign key
                        $this->pendingTranslationInserts[spl_object_hash($object)][] = $translation;
                    } else {
                        // persist and compute change set for translation
                        if ($wasPersistedSeparetely) {
                            $ea->recomputeSingleObjectChangeset($uow, $translationMetadata, $translation);
                        } else {
                            $om->persist($translation);
                            $uow->computeChangeSet($translationMetadata, $translation);
                        }
                    }
                }
            }

            if ($isInsert && null !== $this->getTranslationInDefaultLocale($oid, $field)) {
                // We can't rely on object field value which is created in non-default locale.
                // If we provide translation for default locale as well, the latter is considered to be trusted
                // and object content should be overridden.
                $wrapped->setPropertyValue($field, $this->getTranslationInDefaultLocale($oid, $field)->getContent());
                $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
                $this->removeTranslationInDefaultLocale($oid, $field);
            }
        }
        $this->translatedInLocale[$oid] = $locale;
        // check if we have default translation and need to reset the translation
        if (!$isInsert && strlen($this->defaultLocale)) {
            $this->validateLocale($this->defaultLocale);
            $modifiedChangeSet = $changeSet;
            foreach ($changeSet as $field => $changes) {
                if (in_array($field, $translatableFields)) {
                    if ($locale !== $this->defaultLocale) {
                        $ea->setOriginalObjectProperty($uow, $oid, $field, $changes[0]);
                        unset($modifiedChangeSet[$field]);
                    }
                }
            }
            $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
            // cleanup current changeset only if working in a another locale different than de default one, otherwise the changeset will always be reverted
            if ($locale !== $this->defaultLocale) {
                $ea->clearObjectChangeSet($uow, $oid);
                // recompute changeset only if there are changes other than reverted translations
                if ($modifiedChangeSet || $this->hasTranslationsInDefaultLocale($oid)) {
                    foreach ($modifiedChangeSet as $field => $changes) {
                        $ea->setOriginalObjectProperty($uow, $oid, $field, $changes[0]);
                    }
                    foreach ($translatableFields as $field) {
                        if (null !== $this->getTranslationInDefaultLocale($oid, $field)) {
                            $wrapped->setPropertyValue($field, $this->getTranslationInDefaultLocale($oid, $field)->getContent());
                            $this->removeTranslationInDefaultLocale($oid, $field);
                        }
                    }
                    $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
                }
            }
        }
    }

    /**
     * Sets the translation object which represents a translation in the default language.
     *
     * @param string $oid   hash of basic entity
     * @param string $field field of basic entity
     * @param object $trans Translation object
     */
    public function setTranslationInDefaultLocale($oid, $field, $trans)
    {
        if (!isset($this->translationInDefaultLocale[$oid])) {
            $this->translationInDefaultLocale[$oid] = [];
        }
        $this->translationInDefaultLocale[$oid][$field] = $trans;
    }

    /**
     * @return bool
     */
    public function isSkipOnLoad()
    {
        return $this->skipOnLoad;
    }

    /**
     * Removes the translation object which represents the translation in the default language.
     *
     * @param string $oid   hash of the basic entity
     * @param string $field field of basic entity
     */
    private function removeTranslationInDefaultLocale($oid, $field)
    {
        if (isset($this->translationInDefaultLocale[$oid])) {
            if (isset($this->translationInDefaultLocale[$oid][$field])) {
                unset($this->translationInDefaultLocale[$oid][$field]);
            }
            if (!$this->translationInDefaultLocale[$oid]) {
                // We removed the final remaining elements from the
                // translationInDefaultLocale[$oid] array, so we might as well
                // completely remove the entry at $oid.
                unset($this->translationInDefaultLocale[$oid]);
            }
        }
    }

    /**
     * Gets the translation object which represents the translation in the default language.
     *
     * @param string $oid   hash of the basic entity
     * @param string $field field of basic entity
     *
     * @return object|null
     */
    private function getTranslationInDefaultLocale($oid, $field)
    {
        if (array_key_exists($oid, $this->translationInDefaultLocale)) {
            if (array_key_exists($field, $this->translationInDefaultLocale[$oid])) {
                $ret = $this->translationInDefaultLocale[$oid][$field];
            } else {
                $ret = null;
            }
        } else {
            $ret = null;
        }

        return $ret;
    }

    /**
     * Check if the object has any translation objects which represents the translation in default language.
     * This is for internal use only.
     *
     * @param string $oid hash of the object
     *
     * @return bool
     */
    public function hasTranslationsInDefaultLocale($oid)
    {
        return array_key_exists($oid, $this->translationInDefaultLocale);
    }

    /**
     * Checks if the translation object belongs to the object in question
     *
     * @param object $trans
     * @param object $object
     *
     * @return bool
     */
    private function belongsToObject(TranslatableAdapter $ea, $trans, $object)
    {
        if ($ea->usesPersonalTranslation(get_class($trans))) {
            return $trans->getObject() === $object;
        }

        return $trans->getForeignKey() === $object->getId()
            && ($trans->getObjectClass() === get_class($object));
    }
}
