<?php

namespace Gedmo\Tool;

use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Interface for a wrapper of a managed object.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface WrapperInterface
{
    /**
     * Get the currently wrapped object.
     *
     * @return object
     */
    public function getObject();

    /**
     * Retrieves a property's value from the wrapped object.
     *
     * @param string $property
     *
     * @return mixed
     */
    public function getPropertyValue($property);

    /**
     * Sets a property's value on the wrapped object.
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return \Gedmo\Tool\WrapperInterface
     */
    public function setPropertyValue($property, $value);

    /**
     * Populates the wrapped object with the given property values.
     *
     * @return static
     */
    public function populate(array $data);

    /**
     * Checks if the identifier is valid.
     *
     * @return bool
     */
    public function hasValidIdentifier();

    /**
     * Get the metadata
     *
     * @return ClassMetadata
     */
    public function getMetadata();

    /**
     * Get the object identifier, single or composite
     *
     * @param bool $single
     *
     * @return array|mixed
     */
    public function getIdentifier($single = true);

    /**
     * Get the root object class name.
     *
     * @return string
     */
    public function getRootObjectName();

    /**
     * Checks if an association is embedded.
     *
     * @param string $field
     *
     * @return bool
     */
    public function isEmbeddedAssociation($field);
}
