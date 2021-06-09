<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Tool\BaseTestCaseMongoODM;
use Translatable\Fixture\Document\Article;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Translatable\\Fixture\\Document\\Article';
    public const TRANSLATION = 'Gedmo\\Translatable\\Document\\Translation';

    private $translatableListener;
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en_us');
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testTranslation()
    {
        // test inserted translations
        $repo = $this->dm->getRepository(self::ARTICLE);
        /*$article = $repo->findOneBy(['title' => 'Title EN']);

        $transRepo = $this->dm->getRepository(self::TRANSLATION);
        $this->assertTrue($transRepo instanceof Document\Repository\TranslationRepository);

        $translations = $transRepo->findTranslations($article);
        $this->assertCount(0, $translations);

        // test second translations
        $this->translatableListener->setTranslatableLocale('de_de');
        $article->setTitle('Title DE');
        $article->setCode('Code DE');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $translations = $transRepo->findTranslations($article);
        $this->assertCount(1, $translations);

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('Title DE', $translations['de_de']['title']);

        $this->assertArrayHasKey('code', $translations['de_de']);
        $this->assertEquals('Code DE', $translations['de_de']['code']);

        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-de-code-de', $translations['de_de']['slug']);

        // test value update
        $this->dm->clear();*/
        $this->translatableListener->setTranslatableLocale('en_us');
        $article = $repo->find($this->articleId);

        $this->assertEquals('Title EN', $article->getTitle());
        $this->assertEquals('Code EN', $article->getCode());
        $this->assertEquals('title-en-code-en', $article->getSlug());

        // test translation update
        /*$article->setTitle('Title EN Updated');
        $article->setCode('Code EN Updated');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $this->assertEquals('Title EN Updated', $article->getTitle());
        $this->assertEquals('Code EN Updated', $article->getCode());

        // test removal of translations
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $this->assertNull($article);

        $translations = $transRepo->findTranslationsByObjectId($this->articleId);
        $this->assertCount(0, $translations);*/
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Title EN');
        $art0->setCode('Code EN');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->articleId = $art0->getId();
        $this->dm->clear();
    }
}
