<?php

namespace Tree\Fixture\Closure;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="closure")
 * @Gedmo\TreeClosure(class="Tree\Fixture\Closure\CategoryWithoutLevelClosure")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\ClosureTreeRepository")
 */
class CategoryWithoutLevel
{
    /**
     * @var \Tree\Fixture\Closure\CategoryWithoutLevelClosure[]
     */
    public $closures;
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeParent
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\ManyToOne(targetEntity="CategoryWithoutLevel", inversedBy="children")
     */
    private $parent;

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

    public function setParent(CategoryWithoutLevel $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function addClosure(CategoryWithoutLevelClosure $closure)
    {
        $this->closures[] = $closure;
    }
}
