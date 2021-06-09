<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Article;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableEntityCollectionTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Translatable\\Fixture\\Article';
    public const COMMENT = 'Translatable\\Fixture\\Comment';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $conn = [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda',
        ];
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldEnsureSolvedIssue234()
    {
        $this->translatableListener->setTranslatableLocale('de');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $repo = $this->em->getRepository(self::TRANSLATION);
        $entity = new Article();
        $entity->setTitle('he'); // is translated to de

        $repo
            ->translate($entity, 'title', 'en', 'my article en')
            ->translate($entity, 'title', 'es', 'my article es')
            ->translate($entity, 'title', 'fr', 'my article fr')
            ->translate($entity, 'title', 'de', 'my article de')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
        $trans = $repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        $this->assertCount(4, $trans);
        $this->assertSame('my article de', $trans['de']['title']); // overrides "he" which would be used if translate for de not called
        $this->assertSame('my article es', $trans['es']['title']);
        $this->assertSame('my article fr', $trans['fr']['title']);
        $this->assertSame('my article en', $trans['en']['title']);
    }

    /**
     * @test
     */
    public function shouldPersistMultipleTranslations()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $translations = $repo->findTranslations($sport);

        $this->assertCount(2, $translations);

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('sport de', $translations['de_de']['title']);
        $this->assertEquals('content de', $translations['de_de']['content']);

        $this->assertArrayHasKey('ru_ru', $translations);
        $this->assertArrayHasKey('title', $translations['ru_ru']);
        $this->assertArrayHasKey('content', $translations['ru_ru']);
        $this->assertEquals('sport ru', $translations['ru_ru']['title']);
        $this->assertEquals('content ru', $translations['ru_ru']['content']);
    }

    /**
     * @test
     */
    public function shouldUpdateTranslation()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $repo
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
        ;
        $this->em->flush();

        $translations = $repo->findTranslations($sport);
        $this->assertCount(2, $translations);

        $this->assertArrayHasKey('ru_ru', $translations);
        $this->assertArrayHasKey('title', $translations['ru_ru']);
        $this->assertArrayHasKey('content', $translations['ru_ru']);
        $this->assertEquals('sport ru change', $translations['ru_ru']['title']);
        $this->assertEquals('content ru change', $translations['ru_ru']['content']);
    }

    /**
     * @test
     */
    public function shouldUpdateMultipleTranslations()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $repo
            ->translate($sport, 'title', 'lt_lt', 'sport lt')
            ->translate($sport, 'content', 'lt_lt', 'content lt')
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
            ->translate($sport, 'title', 'en_us', 'sport en update')
            ->translate($sport, 'content', 'en_us', 'content en update')
        ;
        $this->em->flush();

        $this->assertEquals('sport en update', $sport->getTitle());
        $this->assertEquals('content en update', $sport->getContent());

        $translations = $repo->findTranslations($sport);
        $this->assertCount(3, $translations);

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('sport de', $translations['de_de']['title']);
        $this->assertEquals('content de', $translations['de_de']['content']);

        $this->assertArrayHasKey('ru_ru', $translations);
        $this->assertArrayHasKey('title', $translations['ru_ru']);
        $this->assertArrayHasKey('content', $translations['ru_ru']);
        $this->assertEquals('sport ru change', $translations['ru_ru']['title']);
        $this->assertEquals('content ru change', $translations['ru_ru']['content']);

        $this->assertArrayHasKey('lt_lt', $translations);
        $this->assertArrayHasKey('title', $translations['lt_lt']);
        $this->assertArrayHasKey('content', $translations['lt_lt']);
        $this->assertEquals('sport lt', $translations['lt_lt']['title']);
        $this->assertEquals('content lt', $translations['lt_lt']['content']);
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setContent('about sport');

        $repo
            ->translate($sport, 'title', 'de_de', 'sport de')
            ->translate($sport, 'content', 'de_de', 'content de')
            ->translate($sport, 'title', 'ru_ru', 'sport ru')
            ->translate($sport, 'content', 'ru_ru', 'content ru')
        ;

        $this->em->persist($sport);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
        ];
    }
}
