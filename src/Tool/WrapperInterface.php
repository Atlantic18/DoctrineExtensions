<?php

namespace Gedmo\Tool;

/**
 * Object wrapper interface
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface WrapperInterface
{
    /**
     * Get currently wrapped object
     * etc.: entity, document
     *
     * @return object
     */
    public function getObject();

    /**
     * Extract property value from object
     *
     * @param string $property
     *
     * @return mixed
     */
    public function getPropertyValue($property);

    /**
     * Set the property
     *
     * @param string $property
     * @param mixed  $value
     *
     * @return \Gedmo\Tool\WrapperInterface
     */
    public function setPropertyValue($property, $value);

    /**
     * Populates the object with given property values
     *
     * @return static
     */
    public function populate(array $data);

    /**
     * Checks if identifier is valid
     *
     * @return bool
     */
    public function hasValidIdentifier();

    /**
     * Get metadata
     *
     * @return object
     */
    public function getMetadata();

    /**
     * Get the object identifier, single or composite
     *
     * @param bool $single
     * @param bool $flatten
     *
     * @return array|mixed
     */
    public function getIdentifier($single = true, $flatten = false);

    /**
     * Get root object class name
     *
     * @return string
     */
    public function getRootObjectName();

    /**
     * Chechks if association is embedded
     *
     * @param string $field
     *
     * @return bool
     */
    public function isEmbeddedAssociation($field);
}
