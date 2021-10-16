<?php

namespace Gedmo\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use function class_exists;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * This is a base event subscriber providing common
 * functionality to event listeners for all extensions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class MappedEventSubscriber implements EventSubscriber
{
    /**
     * List of cached object configurations.
     *
     * Leaving it static for reasons to look into
     * other listener configuration
     *
     * @var array<string, array<string, mixed>>
     */
    protected static $configurations = [];

    /**
     * Listener name.
     *
     * @var string
     */
    protected $name;

    /**
     * Metadata factories used to read the extension metadata for each object manager.
     *
     * @var array<string, ExtensionMetadataFactory>
     */
    private $extensionMetadataFactory = [];

    /**
     * List of event adapters used for this listener
     *
     * @var array<string, AdapterInterface>
     */
    private $adapters = [];

    /**
     * Custom annotation reader
     *
     * @var object
     */
    private $annotationReader;

    /**
     * @var AnnotationReader
     */
    private static $defaultAnnotationReader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $parts = explode('\\', $this->getNamespace());
        $this->name = end($parts);
    }

    /**
     * Get an event adapter to handle event specific methods.
     *
     * @return AdapterInterface
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if event is not recognized
     */
    protected function getEventAdapter(EventArgs $args)
    {
        $class = get_class($args);
        if (preg_match('@Doctrine\\\([^\\\]+)@', $class, $m) && in_array($m[1], ['ODM', 'ORM'])) {
            if (!isset($this->adapters[$m[1]])) {
                $adapterClass = $this->getNamespace().'\\Mapping\\Event\\Adapter\\'.$m[1];
                if (!class_exists($adapterClass)) {
                    $adapterClass = 'Gedmo\\Mapping\\Event\\Adapter\\'.$m[1];
                }
                $this->adapters[$m[1]] = new $adapterClass();
            }
            $this->adapters[$m[1]]->setEventArgs($args);

            return $this->adapters[$m[1]];
        } else {
            throw new \Gedmo\Exception\InvalidArgumentException('Event mapper does not support event arg class: '.$class);
        }
    }

    /**
     * Get the configuration for a specific object class.
     *
     * If a cache driver is present, it scans that as well.
     *
     * @param string $class
     *
     * @return array
     */
    public function getConfiguration(ObjectManager $objectManager, $class)
    {
        $config = [];
        if (isset(self::$configurations[$this->name][$class])) {
            $config = self::$configurations[$this->name][$class];
        } else {
            $factory = $objectManager->getMetadataFactory();
            $cacheDriver = $factory->getCacheDriver();
            if ($cacheDriver) {
                $cacheId = ExtensionMetadataFactory::getCacheId($class, $this->getNamespace());
                if (false !== ($cached = $cacheDriver->fetch($cacheId))) {
                    self::$configurations[$this->name][$class] = $cached;
                    $config = $cached;
                } else {
                    // re-generate metadata on cache miss
                    $this->loadMetadataForObjectClass($objectManager, $factory->getMetadataFor($class));
                    if (isset(self::$configurations[$this->name][$class])) {
                        $config = self::$configurations[$this->name][$class];
                    }
                }

                $objectClass = $config['useObjectClass'] ?? $class;
                if ($objectClass !== $class) {
                    $this->getConfiguration($objectManager, $objectClass);
                }
            }
        }

        return $config;
    }

    /**
     * Get the metadata factory for an object manager.
     *
     * @return ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory(ObjectManager $objectManager)
    {
        $oid = spl_object_hash($objectManager);
        if (!isset($this->extensionMetadataFactory[$oid])) {
            if (is_null($this->annotationReader)) {
                // create default annotation reader for extensions
                $this->annotationReader = $this->getDefaultAnnotationReader();
            }
            $this->extensionMetadataFactory[$oid] = new ExtensionMetadataFactory(
                $objectManager,
                $this->getNamespace(),
                $this->annotationReader
            );
        }

        return $this->extensionMetadataFactory[$oid];
    }

    /**
     * Set annotation reader class
     * since older doctrine versions do not provide an interface
     * it must provide these methods:
     *     getClassAnnotations([reflectionClass])
     *     getClassAnnotation([reflectionClass], [name])
     *     getPropertyAnnotations([reflectionProperty])
     *     getPropertyAnnotation([reflectionProperty], [name])
     *
     * @param Reader $reader - annotation reader class
     */
    public function setAnnotationReader($reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Scans the objects for extended annotations.
     *
     * Event subscribers must subscribe to the loadClassMetadata event.
     *
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForObjectClass(ObjectManager $objectManager, $metadata)
    {
        $factory = $this->getExtensionMetadataFactory($objectManager);
        try {
            $config = $factory->getExtensionMetadata($metadata);
        } catch (\ReflectionException $e) {
            // entity\document generator is running
            $config = false; // will not store a cached version, to remap later
        }
        if ($config) {
            self::$configurations[$this->name][$metadata->name] = $config;
        }
    }

    /**
     * Get the namespace of the extension event subscriber.
     *
     * Used for cache id of extensions, also to know where
     * to find mapping drivers and event adapters.
     *
     * @return string
     */
    abstract protected function getNamespace();

    /**
     * Retrieve a default annotation reader for extensions,
     * lazily creating it on the first call to this method.
     *
     * @return AnnotationReader
     */
    private function getDefaultAnnotationReader()
    {
        if (null === self::$defaultAnnotationReader) {
            AnnotationRegistry::registerAutoloadNamespace('Gedmo\\Mapping\\Annotation', __DIR__.'/../../');

            $reader = new AnnotationReader();

            if (class_exists(ArrayAdapter::class)) {
                $reader = new PsrCachedReader($reader, new ArrayAdapter());
            } elseif (class_exists(ArrayCache::class)) {
                $reader = new PsrCachedReader($reader, CacheAdapter::wrap(new ArrayCache()));
            }

            self::$defaultAnnotationReader = $reader;
        }

        return self::$defaultAnnotationReader;
    }

    /**
     * Sets the value for a mapped field.
     *
     * @param object $object
     * @param string $field
     * @param mixed  $oldValue
     * @param mixed  $newValue
     */
    protected function setFieldValue(AdapterInterface $adapter, $object, $field, $oldValue, $newValue)
    {
        $manager = $adapter->getObjectManager();
        $meta = $manager->getClassMetadata(get_class($object));
        $uow = $manager->getUnitOfWork();

        $meta->getReflectionProperty($field)->setValue($object, $newValue);
        $uow->propertyChanged($object, $field, $oldValue, $newValue);
        $adapter->recomputeSingleObjectChangeSet($uow, $meta, $object);
    }
}
