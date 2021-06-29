<?php

namespace Wrapper\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CompositeRelation
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Wrapper\Fixture\Entity\Article")
     */
    private $article;
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(length=128)
     */
    private $title;

    public function __construct(Article $articleOne, $status)
    {
        $this->article = $articleOne;
        $this->status = $status;
    }

    public function getArticle()
    {
        return $this->article;
    }

    public function getStatus()
    {
        return $this->status;
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
