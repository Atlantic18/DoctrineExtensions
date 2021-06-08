<?php

namespace Tree\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Node as NodeInterface;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Tree\Entity\Repository\NestedTreeRepository")
 * @Gedmo\Tree(type="nested")
 * @ORM\HasLifecycleCallbacks
 */
class CategoryUuid implements NodeInterface
{
    /**
     * @ORM\Column(name="id", type="string", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="CategoryUuid", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    private $parentId;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $level;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="string")
     */
    private $root;

    /**
     * @ORM\OneToMany(targetEntity="CategoryUuid", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="category")
     */
    private $comments;

    /**
     * Creates a random uuid on persist
     *
     * @return void
     * @ORM\PrePersist
     */
    public function createId()
    {
        $this->id = bin2hex(pack('N2', random_int(0, mt_getrandmax()), random_int(0, mt_getrandmax())));
    }

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

    public function setParent(CategoryUuid $parent)
    {
        $this->parentId = $parent;
    }

    public function getParent()
    {
        return $this->parentId;
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
        return $this->level;
    }
}
