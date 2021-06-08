<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * List of types which are valid for position fields
     *
     * @var array
     */
    private $validTypes = [
        'int',
        'integer',
        'smallint',
        'bigint',
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']) && in_array('sortablePosition', $fieldMapping['gedmo'])) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['position'] = $field;
                }
            }
            $this->readSortableGroups($mapping['fields'], $config);
        }
        if (isset($mapping['manyToOne'])) {
            $this->readSortableGroups($mapping['manyToOne'], $config);
        }
        if (isset($mapping['manyToMany'])) {
            $this->readSortableGroups($mapping['manyToMany'], $config);
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['position'])) {
            throw new InvalidMappingException("Missing property: 'position' in class - {$meta->name}");
        }
    }

    private function readSortableGroups($mapping, array &$config)
    {
        foreach ($mapping as $field => $fieldMapping) {
            if (isset($fieldMapping['gedmo']) && in_array('sortableGroup', $fieldMapping['gedmo'])) {
                if (!isset($config['groups'])) {
                    $config['groups'] = [];
                }
                $config['groups'][] = $field;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    /**
     * Checks if $field type is valid as SortablePosition field
     *
     * @param object $meta
     * @param string $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
