<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author Derek J. Lambert <dlambert@dereklambert.com>
 */
abstract class AbstractAnnotationDriver implements AttributeDriverInterface
{
    /**
     * Annotation reader instance
     *
     * @var AttributeReader
     */
    protected $reader;

    /**
     * Original driver if it is available
     *
     * @var MappingDriver
     */
    protected $_originalDriver;

    /**
     * List of types which are valid for extension
     *
     * @var string[]
     */
    protected $validTypes = [];

    /**
     * Set the annotation reader instance
     *
     * @return void
     */
    public function setAnnotationReader(AttributeReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param MappingDriver $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }

    /**
     * @param ClassMetadata $meta
     *
     * @return \ReflectionClass
     *
     * @phpstan-return \ReflectionClass<object>
     */
    public function getMetaReflectionClass($meta)
    {
        return $meta->getReflectionClass();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
    }

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes, true);
    }

    /**
     * Try to find out related class name out of mapping
     *
     * @param ClassMetadata $metadata the mapped class metadata
     * @param string        $name     the related object class name
     *
     * @return string related class name or empty string if does not exist
     *
     * @phpstan-param class-string|string $name
     *
     * @phpstan-return class-string|''
     */
    protected function getRelatedClassName($metadata, $name)
    {
        if (class_exists($name) || interface_exists($name)) {
            return $name;
        }
        $refl = $metadata->getReflectionClass();
        $ns = $refl->getNamespaceName();
        $className = $ns.'\\'.$name;

        return class_exists($className) ? $className : '';
    }
}
