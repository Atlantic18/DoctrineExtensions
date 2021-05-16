<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Translatable\Fixture\Issue165\SimpleArticle;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue165Test extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Translatable\Fixture\Issue165\SimpleArticle';
    public const TRANSLATION = 'Gedmo\\Translatable\\Document\\Translation';

    private $translatableListener;
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockDocumentManager($evm);
    }

    /**
     * @test
     */
    public function shouldPersistUntranslatedFields()
    {
        $article = new SimpleArticle();
        $article->setTitle('en');
        $article->setContent('en');
        $article->setUntranslated('en');

        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals('en', $article->getUntranslated());

        $this->translatableListener->setTranslatableLocale('ru');

        $article->setTitle('ru');
        $article->setContent('ru');
        $article->setUntranslated('ru');

        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals('ru', $article->getUntranslated());

        $this->translatableListener->setTranslatableLocale('de');

        $newarticle = new SimpleArticle();
        $newarticle->setTitle('de');
        $newarticle->setContent('de');
        $newarticle->setUntranslated('de');

        $this->dm->persist($newarticle);
        $this->dm->flush();
        $this->dm->refresh($article);

        $this->assertEquals('de', $newarticle->getUntranslated());

        $this->translatableListener->setTranslatableLocale('en');

        $id = $newarticle->getId();
        $newarticle = $this->dm->getRepository('Translatable\Fixture\Issue165\SimpleArticle')->find($id);

        $newarticle->setTitle('en');
        $newarticle->setContent('en');
        $newarticle->setUntranslated('en');

        $this->dm->persist($newarticle);
        $this->dm->flush();
        $this->dm->refresh($newarticle);

        $this->assertEquals('en', $newarticle->getUntranslated());

        $this->translatableListener->setTranslatableLocale('de');
        $newarticle->setTitle('de2');
        $newarticle->setContent('de2');
        $newarticle->setUntranslated('de2');

        $this->dm->persist($newarticle);
        $this->dm->flush();

        $id = $newarticle->getId();
        $newarticle = $this->dm->getRepository('Translatable\Fixture\Issue165\SimpleArticle')->find($id);

        $this->assertEquals('de2', $newarticle->getUntranslated());
    }
}
