<?php

namespace ReferenceIntegrity\Fixture\Document\OnePull;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ODM\Document(collection="types")
 */
class Type
{
    /**
     * @ODM\Id
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\Field(type="string")
     */
    private $identifier;

    /**
     * @ODM\ReferenceOne(targetDocument="ReferenceIntegrity\Fixture\Document\OnePull\Article", mappedBy="types")
     * @Gedmo\ReferenceIntegrity("pull")
     *
     * @var Article
     */
    protected $article;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setArticle(Article $article)
    {
        $this->article = $article;
    }

    /**
     * @return Article $article
     */
    public function getArticle()
    {
        return $this->article;
    }
}
