<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * List of types which are valid for blame
     *
     * @var array
     */
    private $validTypes = [
        'one',
        'string',
        'int',
    ];

    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $mapping = $this->_getMapping($meta->name);

        if ($mapping->field !== null) {
            /**
             * @var \SimpleXmlElement
             */
            foreach ($mapping->field as $fieldMapping) {
                $fieldMappingDoctrine = $fieldMapping;
                $fieldMapping = $fieldMapping->children(self::GEDMO_NAMESPACE_URI);
                if ($fieldMapping->blameable !== null) {
                    /**
                     * @var \SimpleXmlElement
                     */
                    $data = $fieldMapping->blameable;

                    $field = $this->_getAttribute($fieldMappingDoctrine, 'name');
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' or a reference in class - {$meta->name}");
                    }
                    if (!$this->_isAttributeSet($data, 'on') || !in_array($this->_getAttribute($data, 'on'), ['update', 'create', 'change'])) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                    }

                    if ('change' == $this->_getAttribute($data, 'on')) {
                        if ($this->_isAttributeSet($data, 'field') === '') {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->name}");
                        }
                        $trackedFieldAttribute = $this->_getAttribute($data, 'field');
                        $valueAttribute = $this->_isAttributeSet($data, 'value') !== '' ? $this->_getAttribute($data, 'value') : null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
                        }
                        $field = [
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        ];
                    }
                    $config[$this->_getAttribute($data, 'on')][] = $field;
                }
            }
        }

        if (isset($mapping->{'many-to-one'})) {
            foreach ($mapping->{'many-to-one'} as $fieldMapping) {
                $field = $this->_getAttribute($fieldMapping, 'field');
                $fieldMapping = $fieldMapping->children(self::GEDMO_NAMESPACE_URI);
                if (property_exists($fieldMapping, 'blameable') && $fieldMapping->blameable !== null) {
                    $data = $fieldMapping->blameable;
                    if (!$meta->isSingleValuedAssociation($field)) {
                        throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a one-to-many relation or a string field - {$meta->name}");
                    }
                    if (!$this->_isAttributeSet($data, 'on') || !in_array($this->_getAttribute($data, 'on'), ['update', 'create', 'change'])) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                    }

                    if ('change' == $this->_getAttribute($data, 'on')) {
                        if ($this->_isAttributeSet($data, 'field') === '') {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->name}");
                        }
                        $trackedFieldAttribute = $this->_getAttribute($data, 'field');
                        $valueAttribute = $this->_isAttributeSet($data, 'value') !== '' ? $this->_getAttribute($data, 'value') : null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
                        }
                        $field = [
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        ];
                    }
                    $config[$this->_getAttribute($data, 'on')][] = $field;
                }
            }
        }
    }

    /**
     * Checks if $field type is valid
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
