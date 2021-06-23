<?php

namespace Gedmo\Translatable\Entity\MappedSuperclass;

use Doctrine\ORM\Mapping as ORM;

/**
 * Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation
 *
 * @ORM\MappedSuperclass
 */
abstract class AbstractPersonalTranslation
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    protected $field;

    /**
     * Related entity with ManyToOne relation
     * must be mapped by user
     *
     * @var object
     */
    protected $object;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /**
     * Get id
     *
     * @return int|null $id
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
     * @return string $field
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
     * Get related object
     *
     * @return object
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
