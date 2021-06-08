<?php

namespace Timestampable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="Timestampable\Fixture\Document\Type")
     */
    private $type;

    /**
     * @var string
     *
     * @ODM\Field(type="timestamp")
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    private $published;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     * @Gedmo\Timestampable(on="change", field="isReady", value=true)
     */
    private $ready;

    /**
     * @var bool
     *
     * @ODM\Field(type="boolean")
     */
    private $isReady = false;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getPublished()
    {
        return $this->published;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $published
     */
    public function setPublished(\DateTimeInterface $published)
    {
        $this->published = $published;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $updated
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;
    }

    public function setReady($ready)
    {
        $this->ready = $ready;

        return $this;
    }

    public function getReady()
    {
        return $this->ready;
    }

    public function setIsReady($isReady)
    {
        $this->isReady = $isReady;

        return $this;
    }

    public function getIsReady()
    {
        return $this->isReady;
    }
}
