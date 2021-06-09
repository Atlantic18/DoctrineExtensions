<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\File;
use Translatable\Fixture\Image;
use Translatable\Fixture\TemplatedArticle;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InheritanceTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Translatable\\Fixture\\TemplatedArticle';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    public const FILE = 'Translatable\\Fixture\\File';
    public const IMAGE = 'Translatable\\Fixture\\Image';

    public const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleMappedSuperclass()
    {
        $article = new TemplatedArticle();
        $article->setName('name in en');
        $article->setContent('content in en');
        $article->setTitle('title in en');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $translations = $repo->findTranslations($article);
        $this->assertCount(0, $translations);

        // test second translations
        $article = $this->em->getRepository(self::ARTICLE)->find(1);
        $this->translatableListener->setTranslatableLocale('de');
        $article->setName('name in de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($article);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de', $translations);

        $this->assertArrayHasKey('name', $translations['de']);
        $this->assertEquals('name in de', $translations['de']['name']);

        $this->assertArrayHasKey('title', $translations['de']);
        $this->assertEquals('title in de', $translations['de']['title']);

        $this->assertArrayHasKey('content', $translations['de']);
        $this->assertEquals('content in de', $translations['de']['content']);
    }

    /**
     * @test
     */
    public function shouldHandleInheritedTranslationsThroughBaseObjectClass()
    {
        $file = new File();
        $file->setSize(500);
        $file->setName('file en');

        $image = new Image();
        $image->setMime('mime en');
        $image->setName('image en');
        $image->setSize(445);

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $file->setName('file de');

        $image->setName('image de');
        $image->setMime('mime de');

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();
        $this->em->clear();

        $dql = 'SELECT f FROM '.self::FILE.' f';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $files = $q->getArrayResult();
        $this->assertCount(2, $files);
        $this->assertEquals('image de', $files[0]['name']);
        $this->assertEquals('file de', $files[1]['name']);

        // test loading in locale
        $images = $this->em->getRepository(self::IMAGE)->findAll();
        $this->assertCount(1, $images);
        $this->assertEquals('image de', $images[0]->getName());
        $this->assertEquals('mime de', $images[0]->getMime());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::FILE,
            self::IMAGE,
        ];
    }
}
