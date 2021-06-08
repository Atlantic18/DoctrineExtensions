<?php

namespace Translatable\Fixture\Document\Personal;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoODM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @Gedmo\TranslationEntity(class="Translatable\Fixture\Document\Personal\ArticleTranslation")
 * @MongoODM\Document(collection="articles")
 */
class Article
{
    public $code;
    public $slug;
    /** @MongoODM\Id */
    private $id;

    /**
     * @Gedmo\Translatable
     * @MongoODM\Field(type="string")
     */
    private $title;

    /**
     * @MongoODM\ReferenceMany(targetDocument="Translatable\Fixture\Document\Personal\ArticleTranslation", mappedBy="object")
     */
    private $translations;

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(PersonalArticleTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
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
}
