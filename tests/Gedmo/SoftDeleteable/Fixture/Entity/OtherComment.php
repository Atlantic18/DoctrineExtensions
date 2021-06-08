<?php

namespace SoftDeleteable\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OtherComment
{
    public $deletedAt;
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="comment", type="string")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="OtherArticle", inversedBy="comments")
     */
    private $article;

    public function getId()
    {
        return $this->id;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setArticle(OtherArticle $article)
    {
        $this->article = $article;
    }

    public function getArticle()
    {
        return $this->article;
    }
}
