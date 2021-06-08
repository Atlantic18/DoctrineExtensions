<?php

namespace Sortable\Fixture\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="kids")
 */
class Kid
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $lastname;

    /**
     * @Gedmo\SortablePosition
     * @ODM\Field(type="int")
     */
    protected $position;

    /**
     * @Gedmo\SortableGroup
     * @ODM\Field(type="date")
     */
    protected $birthdate;

    public function getId()
    {
        return $this->id;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $birthdate
     */
    public function setBirthdate(\DateTimeInterface $birthdate)
    {
        $this->birthdate = $birthdate;
    }

    public function getBirthdate()
    {
        return $this->birthdate;
    }
}
