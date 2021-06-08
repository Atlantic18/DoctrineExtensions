<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * List of types which are valid for position field
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
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->name);

        if (isset($xml->field)) {
            foreach ($xml->field as $mappingDoctrine) {
                $mapping = $mappingDoctrine->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->{'sortable-position'})) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['position'] = $field;
                }
            }
            $this->readSortableGroups($xml->field, $config, 'name');
        }

        // Search for sortable-groups in association mappings
        if (isset($xml->{'many-to-one'})) {
            $this->readSortableGroups($xml->{'many-to-one'}, $config);
        }

        // Search for sortable-groups in association mappings
        if (isset($xml->{'many-to-many'})) {
            $this->readSortableGroups($xml->{'many-to-many'}, $config);
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['position'])) {
            throw new InvalidMappingException("Missing property: 'position' in class - {$meta->name}");
        }
    }

    /**
     * @param \SimpleXMLElement[] $mapping
     * @param string              $fieldAttr
     */
    private function readSortableGroups($mapping, array &$config, $fieldAttr = 'field')
    {
        foreach ($mapping as $mappingDoctrine) {
            $map = $mappingDoctrine->children(self::GEDMO_NAMESPACE_URI);

            $field = $this->_getAttribute($mappingDoctrine, $fieldAttr);
            if (isset($map->{'sortable-group'})) {
                if (!isset($config['groups'])) {
                    $config['groups'] = [];
                }
                $config['groups'][] = $field;
            }
        }
    }

    /**
     * Checks if $field type is valid as Sortable Position field
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
