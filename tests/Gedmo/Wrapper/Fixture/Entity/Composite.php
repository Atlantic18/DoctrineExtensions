<?php

namespace Wrapper\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Composite
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $one;
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $two;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    public function __construct($one, $two)
    {
        $this->one = $one;
        $this->two = $two;
    }

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
