<?php

namespace Sluggable\Fixture\Issue1962;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriment", type="string")
 * @ORM\DiscriminatorMap({"car" = "Car", "bus" = "Bus"})
 */
abstract class Vehicle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}
