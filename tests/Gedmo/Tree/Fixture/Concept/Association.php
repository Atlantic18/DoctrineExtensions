<?php

namespace Tree\Fixture\Concept;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 */
class Association extends Concept
{
    /**
     * @var Association
     *
     * @ORM\ManyToOne(targetEntity="Association")
     * @Gedmo\TreeRoot
     */
    public $root;

    /**
     * @var Association
     *
     * @ORM\ManyToOne(targetEntity="Association", inversedBy="children")
     * @Gedmo\TreeParent
     */
    public $parent;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Association", mappedBy="parent")
     */
    public $children;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    public $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    public $rgt;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    public $lvl;

    public function __construct($name)
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @param Association $root
     * @return Association
     */
    public function setRoot(Association $root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * @param Association $parent
     * @return Association
     */
    public function setParent(Association $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getLeft()
    {
        return $this->lft;
    }

    public function getRight()
    {
        return $this->rgt;
    }

    public function getLevel()
    {
        return $this->lvl;
    }
}
