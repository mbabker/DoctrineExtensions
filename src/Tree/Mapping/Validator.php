<?php

namespace Gedmo\Tree\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * Helper class to validate the mapping configurations for the Tree extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Validator
{
    /**
     * List of types which are valid for tree fields
     *
     * @var string[]
     */
    private $validTypes = [
        'integer',
        'smallint',
        'bigint',
        'int',
    ];

    /**
     * List of types which are valid for the path (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathTypes = [
        'string',
        'text',
    ];

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathSourceTypes = [
        'id',
        'integer',
        'smallint',
        'bigint',
        'string',
        'int',
        'float',
    ];

    /**
     * List of types which are valid for the path hash (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathHashTypes = [
        'string',
    ];

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var string[]
     */
    private $validRootTypes = [
        'integer',
        'smallint',
        'bigint',
        'int',
        'string',
        'guid',
    ];

    /**
     * Checks if the given field type is valid.
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes);
    }

    /**
     * Checks if the field type is valid for a path field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPath($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathTypes);
    }

    /**
     * Checks if the field type is valid for a path source field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPathSource($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathSourceTypes);
    }

    /**
     * Checks if the field type is valid for a path hash field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPathHash($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathHashTypes);
    }

    /**
     * Checks if the field type is valid for a lock time field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForLockTime($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && ('date' === $mapping['type'] || 'datetime' === $mapping['type'] || 'timestamp' === $mapping['type']);
    }

    /**
     * Checks if the field type is valid for a root field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForRoot($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validRootTypes);
    }

    /**
     * Validates metadata for a nested tree configuration
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     */
    public function validateNestedTreeMetadata($meta, array $config)
    {
        $missingFields = [];
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['left'])) {
            $missingFields[] = 'left';
        }
        if (!isset($config['right'])) {
            $missingFields[] = 'right';
        }
        if ($missingFields) {
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for a Closure tree configuration
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     */
    public function validateClosureTreeMetadata($meta, array $config)
    {
        $missingFields = [];
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['closure'])) {
            $missingFields[] = 'closure class';
        }
        if ($missingFields) {
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for a materialized path tree configuration
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     */
    public function validateMaterializedPathTreeMetadata($meta, array $config)
    {
        $missingFields = [];
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['path'])) {
            $missingFields[] = 'path';
        }
        if (!isset($config['path_source'])) {
            $missingFields[] = 'path_source';
        }
        if ($missingFields) {
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }
}
