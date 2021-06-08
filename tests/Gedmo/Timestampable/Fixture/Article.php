<?php

namespace Timestampable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;

/**
 * @ORM\Entity
 */
class Article implements Timestampable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(name="body", type="string")
     */
    private $body;

    /**
     * @ORM\OneToMany(targetEntity="Timestampable\Fixture\Comment", mappedBy="article")
     */
    private $comments;

    /**
     * @ORM\Embedded(class="Timestampable\Fixture\Author")
     */
    private $author;

    /**
     * @var datetime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="date")
     */
    private $created;

    /**
     * @var datetime
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable
     */
    private $updated;

    /**
     * @var datetime
     *
     * @ORM\Column(name="published", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     */
    private $published;

    /**
     * @var datetime
     *
     * @ORM\Column(name="content_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    private $contentChanged;
    /**
     * @var datetime
     *
     * @ORM\Column(name="author_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"author.name", "author.email"})
     */
    private $authorChanged;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    private $type;

    public function setType($type)
    {
        $this->type = $type;
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

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(Author $author)
    {
        $this->author = $author;
    }

    /**
     * Get created
     *
     * @return datetime $created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $created
     */
    public function setCreated(\DateTimeInterface $created)
    {
        $this->created = $created;
    }

    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $published
     */
    public function setPublished(\DateTimeInterface $published)
    {
        $this->published = $published;
    }

    /**
     * Get updated
     *
     * @return datetime $updated
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $updated
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $contentChanged
     */
    public function setContentChanged(\DateTimeInterface $contentChanged)
    {
        $this->contentChanged = $contentChanged;
    }

    public function getContentChanged()
    {
        return $this->contentChanged;
    }

    public function getAuthorChanged()
    {
        return $this->authorChanged;
    }
}
