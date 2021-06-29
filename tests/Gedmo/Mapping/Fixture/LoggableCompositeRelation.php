<?php

namespace Mapping\Fixture;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class LoggableCompositeRelation
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Loggable")
     */
    private $one;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $two;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     * @Gedmo\Versioned
     */
    private $title;

    public function getOne()
    {
        return $this->one;
    }

    public function getTwo()
    {
        return $this->two;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
