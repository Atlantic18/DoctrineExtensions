<?php

namespace Sortable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class Event
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @Gedmo\SortableGroup
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=191)
     */
    private $name;

    /**
     * @var int
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer")
     */
    private $position;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $date
     */
    public function setDateTime(\DateTimeInterface $date)
    {
        $this->dateTime = $date;
    }

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
