<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * This is a xml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specifically for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
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

        if ('entity' == $xmlDoctrine->getName() || 'document' == $xmlDoctrine->getName() || 'mapped-superclass' == $xmlDoctrine->getName()) {
            if (isset($xml->loggable)) {
                /**
                 * @var \SimpleXMLElement;
                 */
                $data = $xml->loggable;
                $config['loggable'] = true;
                if ($this->_isAttributeSet($data, 'log-entry-class')) {
                    $class = $this->_getAttribute($data, 'log-entry-class');
                    if (!$cl = $this->getRelatedClassName($meta, $class)) {
                        throw new InvalidMappingException("LogEntry class: {$class} does not exist.");
                    }
                    $config['logEntryClass'] = $cl;
                }
            }
        }

        if (isset($xmlDoctrine->field)) {
            $this->inspectElementForVersioned($xmlDoctrine->field, $config, $meta);
        }
        if (isset($xmlDoctrine->{'many-to-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'many-to-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'one-to-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'one-to-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'reference-one'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'reference-one'}, $config, $meta);
        }
        if (isset($xmlDoctrine->{'embedded'})) {
            $this->inspectElementForVersioned($xmlDoctrine->{'embedded'}, $config, $meta);
        }

        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof \Doctrine\ODM\MongoDB\Mapping\ClassMetadata && is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->name}");
            }
            if (isset($config['versioned']) && !isset($config['loggable'])) {
                throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->name}");
            }
        }
    }

    /**
     * Searches mappings on element for versioned fields
     *
     * @param object $meta
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, array &$config, $meta)
    {
        foreach ($element as $mapping) {
            $mappingDoctrine = $mapping;
            /**
             * @var \SimpleXmlElement
             */
            $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

            $isAssoc = $this->_isAttributeSet($mappingDoctrine, 'field');
            $field = $this->_getAttribute($mappingDoctrine, $isAssoc ? 'field' : 'name');

            if (isset($mapping->versioned)) {
                if ($isAssoc && !$meta->associationMappings[$field]['isOwningSide']) {
                    throw new InvalidMappingException("Cannot version [{$field}] as it is not the owning side in object - {$meta->name}");
                }
                $config['versioned'][] = $field;
            }
        }
    }
}
