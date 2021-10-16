<?php

namespace Gedmo\Translator;

use Doctrine\Common\Collections\Collection;

/**
 * Proxy class for object translations.
 *
 * @author  Konstantin Kudryashov <ever.zet@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationProxy
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var object
     */
    protected $translatable;

    /**
     * @var string[]
     */
    protected $properties = [];

    /**
     * @var class-string<TranslationInterface>
     */
    protected $class;

    /**
     * @var Collection<array-key, TranslationInterface>
     */
    protected $coll;

    /**
     * Initializes the translation proxy.
     *
     * @param object                                      $translatable Object to translate
     * @param string                                      $locale       Translation locale
     * @param string[]                                    $properties   Object properties to translate
     * @param class-string<TranslationInterface>          $class        Translation object class
     * @param Collection<array-key, TranslationInterface> $coll         Translations collection
     *
     * @throws \InvalidArgumentException if the translation class doesn't implement TranslationInterface
     */
    public function __construct($translatable, $locale, array $properties, $class, Collection $coll)
    {
        $this->translatable = $translatable;
        $this->locale = $locale;
        $this->properties = $properties;
        $this->class = $class;
        $this->coll = $coll;

        $translationClass = new \ReflectionClass($class);
        if (!$translationClass->implementsInterface(TranslationInterface::class)) {
            throw new \InvalidArgumentException(sprintf('Translation class must implement Gedmo\Translator\TranslationInterface, "%s" given', $class));
        }
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $matches = [];
        if (preg_match('/^(set|get)(.*)$/', $method, $matches)) {
            $property = lcfirst($matches[2]);

            if (in_array($property, $this->properties)) {
                switch ($matches[1]) {
                    case 'get':
                        return $this->getTranslatedValue($property);
                    case 'set':
                        if (isset($arguments[0])) {
                            $this->setTranslatedValue($property, $arguments[0]);

                            return $this;
                        }
                }
            }
        }

        $return = call_user_func_array([$this->translatable, $method], $arguments);

        if ($this->translatable === $return) {
            return $this;
        }

        return $return;
    }

    /**
     * @param string $property
     *
     * @return string
     */
    public function __get($property)
    {
        if (in_array($property, $this->properties)) {
            if (method_exists($this, $getter = 'get'.ucfirst($property))) {
                return $this->$getter;
            }

            return $this->getTranslatedValue($property);
        }

        return $this->translatable->$property;
    }

    /**
     * @param string $property
     * @param string $value
     *
     * @return mixed
     */
    public function __set($property, $value)
    {
        if (in_array($property, $this->properties)) {
            if (method_exists($this, $setter = 'set'.ucfirst($property))) {
                return $this->$setter($value);
            }

            return $this->setTranslatedValue($property, $value);
        }

        $this->translatable->$property = $value;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function __isset($property)
    {
        return in_array($property, $this->properties);
    }

    /**
     * Returns the locale name for the current translation proxy instance.
     *
     * @return string
     */
    public function getProxyLocale()
    {
        return $this->locale;
    }

    /**
     * Returns the translated value for specific property.
     *
     * @param string $property property name
     *
     * @return string
     */
    public function getTranslatedValue($property)
    {
        return $this
            ->findOrCreateTranslationForProperty($property, $this->getProxyLocale())
            ->getValue();
    }

    /**
     * Sets the translated value for a property.
     *
     * @param string $property Property name
     * @param string $value    Translation
     */
    public function setTranslatedValue($property, $value)
    {
        $this
            ->findOrCreateTranslationForProperty($property, $this->getProxyLocale())
            ->setValue($value);
    }

    /**
     * Finds an existing translation or creates a new one for the specified property.
     *
     * @param string $property Object property name
     * @param string $locale   Locale name
     *
     * @return TranslationInterface
     */
    private function findOrCreateTranslationForProperty($property, $locale)
    {
        foreach ($this->coll as $translation) {
            if ($locale === $translation->getLocale() && $property === $translation->getProperty()) {
                return $translation;
            }
        }

        /** @var TranslationInterface $translation */
        $translation = new $this->class();
        $translation->setTranslatable($this->translatable);
        $translation->setProperty($property);
        $translation->setLocale($locale);
        $this->coll->add($translation);

        return $translation;
    }
}
