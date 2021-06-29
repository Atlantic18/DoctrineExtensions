<?php

namespace Loggable\Fixture\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class CompositeRelation
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Article")
     */
    private $articleOne;
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Article")
     */
    private $articleTwo;

    /**
     * @ORM\Column(length=128)
     * @Gedmo\Versioned
     */
    private $title;

    public function __construct(Article $articleOne, Article $articleTwo)
    {
        $this->articleOne = $articleOne;
        $this->articleTwo = $articleTwo;
    }

    public function getArticleOne()
    {
        return $this->articleOne;
    }

    public function getArticleTwo()
    {
        return $this->articleTwo;
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
