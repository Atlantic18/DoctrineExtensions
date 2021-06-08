<?php

namespace Sluggable\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Entity
 */
class TransArticleManySlug implements Sluggable, Translatable
{
    /**
     * @var \Sluggable\Fixture\Comment[]|mixed
     */
    public $comments;
    public $page;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", length=64)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $uniqueTitle;

    /**
     * @Gedmo\Slug(fields={"uniqueTitle"})
     * @ORM\Column(type="string", length=128)
     */
    private $uniqueSlug;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", length=16)
     */
    private $code;

    /**
     * @Gedmo\Translatable
     * @Gedmo\Slug(fields={"title", "code"})
     * @ORM\Column(type="string", length=128)
     */
    private $slug;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     */
    private $locale;

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setPage($page)
    {
        $this->page = $page;
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

    public function setUniqueTitle($uniqueTitle)
    {
        $this->uniqueTitle = $uniqueTitle;
    }

    public function getUniqueTitle()
    {
        return $this->uniqueTitle;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getUniqueSlug()
    {
        return $this->uniqueSlug;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
