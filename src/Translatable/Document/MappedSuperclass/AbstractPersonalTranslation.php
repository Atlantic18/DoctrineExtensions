<?php

namespace Gedmo\Translatable\Document\MappedSuperclass;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;

/**
 * Gedmo\Translatable\Document\AbstractPersonalTranslation
 *
 * @MongoODM\MappedSuperclass
 */
abstract class AbstractPersonalTranslation
{
    /**
     * @var string|null
     *
     * @MongoODM\Id
     */
    protected $id;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $locale;

    /**
     * Related document with ManyToOne relation
     * must be mapped by user
     *
     * @var object
     */
    protected $object;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $field;

    /**
     * @var string
     *
     * @MongoODM\Field(type="string")
     */
    protected $content;

    /**
     * Get id
     *
     * @return string|null $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set field
     *
     * @param string $field
     *
     * @return static
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set object related
     *
     * @param object $object
     *
     * @return static
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object related
     *
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
