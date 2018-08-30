<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Gedmo\Exception\InvalidMappingException;
use SimpleXMLElement;

/**
 * The mapping XmlDriver abstract class, defines the
 * metadata extraction function common among all
 * all drivers used on these extensions by file based
 * drivers.
 *
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class Xml extends File
{
    const GEDMO_NAMESPACE_URI = 'http://gediminasm.org/schemas/orm/doctrine-extensions-mapping';
    const DOCTRINE_NAMESPACE_URI = 'http://doctrine-project.org/schemas/orm/doctrine-mapping';

    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.xml';

    /**
     * Get attribute value.
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param SimpleXMLElement $node
     * @param string           $attributeName
     *
     * @return string
     */
    protected function _getAttribute(SimpleXmlElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return (string) $attributes[$attributeName];
    }

    /**
     * Get boolean attribute value.
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param SimpleXMLElement $node
     * @param string           $attributeName
     *
     * @return boolean
     */
    protected function _getBooleanAttribute(SimpleXmlElement $node, $attributeName)
    {
        $rawValue = strtolower($this->_getAttribute($node, $attributeName));
        if ($rawValue === '1' || $rawValue === 'true') {
            return true;
        }
        if ($rawValue === '0' || $rawValue === 'false') {
            return false;
        }
        throw new InvalidMappingException(sprintf("Attribute %s must have a valid boolean value, '%s' found", $attributeName, $this->_getAttribute($node, $attributeName)));
    }

    /**
     * does attribute exist under a specific node
     * As we are supporting namespaces the only way to get to the attributes under a node is to use attributes function on it
     *
     * @param SimpleXMLElement $node
     * @param string           $attributeName
     *
     * @return string
     */
    protected function _isAttributeSet(SimpleXmlElement $node, $attributeName)
    {
        $attributes = $node->attributes();

        return isset($attributes[$attributeName]);
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_string(file_get_contents($file));
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
