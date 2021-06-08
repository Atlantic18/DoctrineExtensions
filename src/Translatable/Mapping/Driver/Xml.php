<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
{
    /**
     * {@inheritdoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        /**
         * @var \SimpleXmlElement
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;

        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if (('entity' == $xmlDoctrine->getName() || 'mapped-superclass' == $xmlDoctrine->getName()) && ($xml->count() && isset($xml->translation))) {
            /**
             * @var \SimpleXmlElement
             */
            $data = $xml->translation;
            if ($this->_isAttributeSet($data, 'locale')) {
                $config['locale'] = $this->_getAttribute($data, 'locale');
            } elseif ($this->_isAttributeSet($data, 'language')) {
                $config['locale'] = $this->_getAttribute($data, 'language');
            }
            if ($this->_isAttributeSet($data, 'entity')) {
                $entity = $this->_getAttribute($data, 'entity');
                if (!$cl = $this->getRelatedClassName($meta, $entity)) {
                    throw new InvalidMappingException("Translation entity class: {$entity} does not exist.");
                }
                $config['translationClass'] = $cl;
            }
        }

        if (property_exists($meta, 'embeddedClasses') && $meta->embeddedClasses) {
            foreach ($meta->embeddedClasses as $propertyName => $embeddedClassInfo) {
                if ($meta->isInheritedEmbeddedClass($propertyName)) {
                    continue;
                }
                $xmlEmbeddedClass = $this->_getMapping($embeddedClassInfo['class']);
                $this->inspectElementsForTranslatableFields($xmlEmbeddedClass, $config, $propertyName);
            }
        }

        if ($xmlDoctrine->{'attribute-overrides'}->count() > 0) {
            foreach ($xmlDoctrine->{'attribute-overrides'}->{'attribute-override'} as $overrideMapping) {
                $this->buildFieldConfiguration($this->_getAttribute($overrideMapping, 'name'), $overrideMapping->field, $config);
            }
        }

        $this->inspectElementsForTranslatableFields($xmlDoctrine, $config);

        if (!$meta->isMappedSuperclass && $config && (is_array($meta->identifier) && count($meta->identifier) > 1)) {
            throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
        }
    }

    private function inspectElementsForTranslatableFields(\SimpleXMLElement $xml, array &$config, $prefix = null)
    {
        if (!isset($xml->field)) {
            return;
        }

        foreach ($xml->field as $mapping) {
            $mappingDoctrine = $mapping;

            $fieldName = $this->_getAttribute($mappingDoctrine, 'name');
            if (null !== $prefix) {
                $fieldName = $prefix.'.'.$fieldName;
            }
            $this->buildFieldConfiguration($fieldName, $mapping, $config);
        }
    }

    private function buildFieldConfiguration($fieldName, \SimpleXMLElement $mapping, array &$config)
    {
        $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);
        if ($mapping->count() > 0 && isset($mapping->translatable)) {
            $config['fields'][] = $fieldName;
            /** @var \SimpleXmlElement $data */
            $data = $mapping->translatable;
            if ($this->_isAttributeSet($data, 'fallback')) {
                $config['fallback'][$fieldName] = 'true' == $this->_getAttribute($data, 'fallback');
            }
        }
    }
}
