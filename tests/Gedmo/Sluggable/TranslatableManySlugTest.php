<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\Translatable;
use Gedmo\Translatable\TranslatableListener;
use Sluggable\Fixture\TransArticleManySlug;
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
class TranslatableManySlugTest extends BaseTestCaseORM
{
    private $articleId;
    private $translatableListener;

    public const ARTICLE = 'Sluggable\\Fixture\\TransArticleManySlug';
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
        $this->assertEquals('the-unique-title', $article->getUniqueSlug());
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

        $this->assertEquals('title in de', $translations['de_DE']['title']);

        $this->assertArrayHasKey('slug', $translations['de_DE']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_DE']['slug']);
    }

    public function testUniqueness()
    {
        $a0 = new TransArticleManySlug();
        $a0->setTitle('the title');
        $a0->setCode('my code');
        $a0->setUniqueTitle('title');

        $this->em->persist($a0);

        $a1 = new TransArticleManySlug();
        $a1->setTitle('the title');
        $a1->setCode('my code');
        $a1->setUniqueTitle('title');

        $this->em->persist($a1);
        $this->em->flush();

        $this->assertEquals('title', $a0->getUniqueSlug());
        $this->assertEquals('title-1', $a1->getUniqueSlug());
        // if its translated maybe should be different
        $this->assertEquals('the-title-my-code-1', $a0->getSlug());
        $this->assertEquals('the-title-my-code-2', $a1->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }

    private function populate()
    {
        $article = new TransArticleManySlug();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
