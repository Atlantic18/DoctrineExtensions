<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validTypes = [
        'string',
        'text',
        'integer',
        'int',
        'datetime',
        'citext',
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                $this->buildFieldConfiguration($field, $fieldMapping, $meta, $config);
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $overrideMapping) {
                $this->buildFieldConfiguration($field, $overrideMapping, $meta, $config);
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
     * Checks if $field type is valid as Sluggable field
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

    private function buildFieldConfiguration($field, array $fieldMapping, $meta, array &$config)
    {
        if (isset($fieldMapping['gedmo']) && isset($fieldMapping['gedmo']['slug'])) {
            $slug = $fieldMapping['gedmo']['slug'];
            if (!$this->isValidField($meta, $field)) {
                throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
            }
            // process slug handlers
            $handlers = [];
            if (isset($slug['handlers'])) {
                foreach ($slug['handlers'] as $handlerClass => $options) {
                    if (!strlen($handlerClass)) {
                        throw new InvalidMappingException("SlugHandler class: {$handlerClass} should be a valid class name in entity - {$meta->name}");
                    }
                    $handlers[$handlerClass] = $options;
                    $handlerClass::validate($handlers[$handlerClass], $meta);
                }
            }
            // process slug fields
            if (empty($slug['fields']) || !is_array($slug['fields'])) {
                throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->name}");
            }
            foreach ($slug['fields'] as $slugField) {
                if (!$meta->hasField($slugField)) {
                    throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $slugField)) {
                    throw new InvalidMappingException("Cannot use field - [{$slugField}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->name}");
                }
            }
            $config['slugs'][$field]['fields'] = $slug['fields'];
            $config['slugs'][$field]['handlers'] = $handlers;
            $config['slugs'][$field]['slug'] = $field;
            $config['slugs'][$field]['style'] = isset($slug['style']) ?
                (string) $slug['style'] : 'default';
            $config['slugs'][$field]['dateFormat'] = isset($slug['dateFormat']) ?
                (string) $slug['dateFormat'] : 'Y-m-d-H:i';
            $config['slugs'][$field]['updatable'] = isset($slug['updatable']) ?
                (bool) $slug['updatable'] : true;
            $config['slugs'][$field]['unique'] = isset($slug['unique']) ?
                (bool) $slug['unique'] : true;
            $config['slugs'][$field]['unique_base'] = $slug['unique_base'] ?? null;
            $config['slugs'][$field]['separator'] = isset($slug['separator']) ?
                (string) $slug['separator'] : '-';
            $config['slugs'][$field]['prefix'] = isset($slug['prefix']) ?
                (string) $slug['prefix'] : '';
            $config['slugs'][$field]['suffix'] = isset($slug['suffix']) ?
                (string) $slug['suffix'] : '';
            if (!$meta->isMappedSuperclass && $meta->isIdentifier($field) && !$config['slugs'][$field]['unique']) {
                throw new InvalidMappingException("Identifier field - [{$field}] slug must be unique in order to maintain primary key in class - {$meta->name}");
            }
            $ubase = $config['slugs'][$field]['unique_base'];
            if (false === $config['slugs'][$field]['unique'] && $ubase) {
                throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
            }
            if ($ubase && !$meta->hasField($ubase) && !$meta->hasAssociation($ubase)) {
                throw new InvalidMappingException("Unable to find [{$ubase}] as mapped property in entity - {$meta->name}");
            }
        }
    }
}
