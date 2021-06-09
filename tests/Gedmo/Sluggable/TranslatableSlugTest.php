<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\Translatable;
use Gedmo\Translatable\TranslatableListener;
use Sluggable\Fixture\Comment;
use Sluggable\Fixture\Page;
use Sluggable\Fixture\TranslatableArticle;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSlugTest extends BaseTestCaseORM
{
    private $articleId;
    private $translatableListener;

    public const ARTICLE = 'Sluggable\\Fixture\\TranslatableArticle';
    public const COMMENT = 'Sluggable\\Fixture\\Comment';
    public const PAGE = 'Sluggable\\Fixture\\Page';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSlugAndTranslation()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        $this->assertEquals('the-title-my-code', $article->getSlug());
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        $this->assertCount(0, $translations);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_DE');
        $article->setCode('code in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_DE', $translations);
        $this->assertCount(3, $translations['de_DE']);

        $this->assertArrayHasKey('code', $translations['de_DE']);
        $this->assertEquals('code in de', $translations['de_DE']['code']);

        $this->assertArrayHasKey('title', $translations['de_DE']);
        $this->assertEquals('title in de', $translations['de_DE']['title']);

        $this->assertArrayHasKey('slug', $translations['de_DE']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_DE']['slug']);
    }

    public function testConcurrentChanges()
    {
        $page = new Page();
        $page->setContent('cont test');

        $a0Page = new Page();
        $a0Page->setContent('bi vv');

        $article0 = $this->em->find(self::ARTICLE, $this->articleId);
        $article0->setCode('cell');
        $article0->setTitle('xx gg');
        $a0Page->addArticle($article0);

        $a0Comment = new Comment();
        $a0Comment->setMessage('the xx message');
        $article0->addComment($a0Comment);
        $this->em->persist($a0Comment);
        $this->em->persist($article0);
        $this->em->persist($a0Page);

        $article1 = new TranslatableArticle();
        $article1->setTitle('art1 test');
        $article1->setCode('cd1 test');

        $article2 = new TranslatableArticle();
        $article2->setTitle('art2 test');
        $article2->setCode('cd2 test');

        $page->addArticle($article1);
        $page->addArticle($article2);

        $comment1 = new Comment();
        $comment1->setMessage('mes1-test');
        $comment2 = new Comment();
        $comment2->setMessage('mes2 test');

        $article1->addComment($comment1);
        $article2->addComment($comment2);

        $this->em->persist($page);
        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($page->getSlug(), 'Cont_Test');
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::PAGE,
            self::TRANSLATION,
        ];
    }

    private function populate()
    {
        $article = new TranslatableArticle();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
