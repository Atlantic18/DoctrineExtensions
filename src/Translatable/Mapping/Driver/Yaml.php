<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Translatable
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
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['translation']['entity'])) {
                $translationEntity = $classMapping['translation']['entity'];
                if (!$cl = $this->getRelatedClassName($meta, $translationEntity)) {
                    throw new InvalidMappingException("Translation entity class: {$translationEntity} does not exist.");
                }
                $config['translationClass'] = $cl;
            }
            if (isset($classMapping['translation']['locale'])) {
                $config['locale'] = $classMapping['translation']['locale'];
            } elseif (isset($classMapping['translation']['language'])) {
                $config['locale'] = $classMapping['translation']['language'];
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                $this->buildFieldConfiguration($field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $overrideMapping) {
                $this->buildFieldConfiguration($field, $overrideMapping, $config);
            }
        }

        if (!$meta->isMappedSuperclass && $config && (is_array($meta->identifier) && count($meta->identifier) > 1)) {
            throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    private function buildFieldConfiguration($field, $fieldMapping, array &$config)
    {
        if (is_array($fieldMapping) && isset($fieldMapping['gedmo']) && (in_array('translatable', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['translatable']))) {
            // fields cannot be overrided and throws mapping exception
            $config['fields'][] = $field;
            if (isset($fieldMapping['gedmo']['translatable']['fallback'])) {
                $config['fallback'][$field] = $fieldMapping['gedmo']['translatable']['fallback'];
            }
        }
    }
}
