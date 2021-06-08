<?php

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * @author Gonzalo Vilaseca <gonzalo.vilaseca@reiss.com>
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    private $validReferences = [
        'referenceOne' => [],
        'referenceMany' => [],
        'referenceManyEmbed' => [],
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo']) && isset($mapping['gedmo']['reference'])) {
            foreach ($mapping['gedmo']['reference'] as $field => $fieldMapping) {
                $reference = $fieldMapping['reference'];

                if (!array_key_exists($reference, $this->validReferences)) {
                    throw new InvalidMappingException($reference.' is not a valid reference, valid references are: '.implode(', ', array_keys($this->validReferences)));
                }

                $config[$reference][$field] = [
                    'field' => $field,
                    'type' => $fieldMapping['type'],
                    'class' => $fieldMapping['class'],
                ];

                if (array_key_exists('mappedBy', $fieldMapping)) {
                    $config[$reference][$field]['mappedBy'] = $fieldMapping['mappedBy'];
                }

                if (array_key_exists('identifier', $fieldMapping)) {
                    $config[$reference][$field]['identifier'] = $fieldMapping['identifier'];
                }

                if (array_key_exists('inversedBy', $fieldMapping)) {
                    $config[$reference][$field]['inversedBy'] = $fieldMapping['inversedBy'];
                }
            }
        }
        $config = array_merge($this->validReferences, $config);
    }

    /**
     * {@inheritdoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}
