<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use SimpleXMLElement;

/**
 * This is an abstract class to implement common functionality
 * for extension XML mapping drivers.
 *
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class Xml extends File
{
    public const GEDMO_NAMESPACE_URI = 'http://gediminasm.org/schemas/orm/doctrine-extensions-mapping';
    public const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.xml';

    /**
     * Get the attribute value.
     *
     * As we are supporting namespaces the only way to get to the attributes under a node is
     * to use the attributes function on it.
     *
     * @param string $attributeName
     *
     * @return string
     */
    protected function _getAttribute(SimpleXMLElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return (string) $attributes[$attributeName];
    }

    /**
     * Get a boolean attribute value.
     *
     * As we are supporting namespaces the only way to get to the attributes under a node is
     * to use the attributes function on it.
     *
     * @param string $attributeName
     *
     * @return bool
     */
    protected function _getBooleanAttribute(SimpleXMLElement $node, $attributeName)
    {
        $rawValue = strtolower($this->_getAttribute($node, $attributeName));
        if ('1' === $rawValue || 'true' === $rawValue) {
            return true;
        }
        if ('0' === $rawValue || 'false' === $rawValue) {
            return false;
        }
        throw new InvalidMappingException(sprintf("Attribute %s must have a valid boolean value, '%s' found", $attributeName, $this->_getAttribute($node, $attributeName)));
    }

    /**
     * Checks if an attribute exists under the given node.
     *
     * As we are supporting namespaces the only way to get to the attributes under a node is
     * to use the attributes function on it.
     *
     * @param string $attributeName
     *
     * @return bool
     */
    protected function _isAttributeSet(SimpleXMLElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return isset($attributes[$attributeName]);
    }

    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        $result = [];
        $xmlElement = simplexml_load_file($file);
        $xmlElement = $xmlElement->children(self::DOCTRINE_NAMESPACE_URI);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = $this->_getAttribute($entityElement, 'name');
                $result[$entityName] = $entityElement;
            }
        } elseif (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = $this->_getAttribute($mappedSuperClass, 'name');
                $result[$className] = $mappedSuperClass;
            }
        }

        return $result;
    }
}
