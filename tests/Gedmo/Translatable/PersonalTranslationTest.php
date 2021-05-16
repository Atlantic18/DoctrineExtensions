<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Personal\Article;
use Translatable\Fixture\Personal\PersonalArticleTranslation;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PersonalTranslationTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Translatable\Fixture\Personal\Article';
    public const TRANSLATION = 'Translatable\Fixture\Personal\PersonalArticleTranslation';
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
    public function shouldPersistDefaultLocaleTranslationIfRequired()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->populate();
        $article = $this->em->find(self::ARTICLE, ['id' => 1]);
        $translations = $article->getTranslations();
        $this->assertCount(3, $translations);
    }

    /**
     * @test
     */
    public function shouldCreateTranslations()
    {
        $this->populate();
        $article = $this->em->find(self::ARTICLE, ['id' => 1]);
        $translations = $article->getTranslations();
        $this->assertCount(2, $translations);
    }

    /**
     * @test
     */
    public function shouldTranslateTheRecord()
    {
        $this->populate();
        $this->translatableListener->setTranslatableLocale('lt');

        $this->startQueryLog();
        $article = $this->em->find(self::ARTICLE, ['id' => 1]);

        $sqlQueriesExecuted = $this->queryAnalyzer->getExecutedQueries();
        $this->assertCount(2, $sqlQueriesExecuted);
        $this->assertEquals('SELECT t0.id AS id_1, t0.locale AS locale_2, t0.field AS field_3, t0.content AS content_4, t0.object_id AS object_id_5 FROM article_translations t0 WHERE t0.object_id = 1', $sqlQueriesExecuted[1]);
        $this->assertEquals('lt', $article->getTitle());
    }

    /**
     * @test
     */
    public function shouldCascadeDeletionsByForeignKeyConstraints()
    {
        if ('sqlite' == $this->em->getConnection()->getDatabasePlatform()->getName()) {
            $this->markTestSkipped('Foreign key constraints does not map in sqlite.');
        }
        $this->populate();
        $this->em->createQuery('DELETE FROM '.self::ARTICLE.' a')->getSingleScalarResult();
        $trans = $this->em->getRepository(self::TRANSLATION)->findAll();

        $this->assertCount(0, $trans);
    }

    /**
     * @test
     */
    public function shouldOverrideTranslationInEntityBeingTranslated()
    {
        $this->translatableListener->setDefaultLocale('de');
        $article = new Article();
        $article->setTitle('override');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('en')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);
        $this->em->persist($article);
        $this->em->flush();

        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
        $this->assertCount(1, $trans);
        $this->assertEquals('override', $trans[0]['content']);
    }

    /**
     * Covers issue #438
     *
     * @test
     */
    public function shouldPersistDefaultLocaleValue()
    {
        $this->translatableListener->setTranslatableLocale('de');
        $article = new Article();
        $article->setTitle('de');

        $deTranslation = new PersonalArticleTranslation();
        $deTranslation
            ->setField('title')
            ->setContent('de')
            ->setObject($article)
            ->setLocale('de')
        ;
        $this->em->persist($deTranslation);

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('en')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);

        $this->em->persist($article);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('en');
        $articles = $this->em->createQuery('SELECT t FROM '.self::ARTICLE.' t')->getArrayResult();
        $this->assertEquals('en', $articles[0]['title']);
        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
        $this->assertCount(2, $trans);
        foreach ($trans as $item) {
            $this->assertEquals($item['locale'], $item['content']);
        }
    }

    /**
     * @test
     */
    public function shouldFindFromIdentityMap()
    {
        $article = new Article();
        $article->setTitle('en');

        $ltTranslation = new PersonalArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $this->em->persist($ltTranslation);
        $this->em->persist($article);
        $this->em->flush();

        $this->startQueryLog();
        $this->translatableListener->setTranslatableLocale('lt');
        $article->setTitle('change lt');

        $this->em->persist($article);
        $this->em->flush();
        $sqlQueriesExecuted = $this->queryAnalyzer->getExecutedQueries();
        $this->assertCount(3, $sqlQueriesExecuted); // one update, transaction start - commit
        $this->assertEquals("UPDATE article_translations SET content = 'change lt' WHERE id = 1", $sqlQueriesExecuted[1]);
    }

    /**
     * @test
     */
    public function shouldBeAbleToUseTranslationQueryHint()
    {
        $this->populate();
        $dql = 'SELECT a.title FROM '.self::ARTICLE.' a';
        $query = $this
            ->em->createQuery($dql)
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION)
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt')
        ;

        $this->startQueryLog();
        $result = $query->getArrayResult();

        $this->assertCount(1, $result);
        $this->assertEquals('lt', $result[0]['title']);
        $sqlQueriesExecuted = $this->queryAnalyzer->getExecutedQueries();
        $this->assertCount(1, $sqlQueriesExecuted);
        $this->assertEquals("SELECT CAST(t1_.content AS VARCHAR(128)) AS title_0 FROM Article a0_ LEFT JOIN article_translations t1_ ON t1_.locale = 'lt' AND t1_.field = 'title' AND t1_.object_id = a0_.id", $sqlQueriesExecuted[0]);
    }

    private function populate()
    {
        $article = new Article();
        $article->setTitle('en');

        $this->em->persist($article);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $article->setTitle('de');

        $ltTranslation = new PersonalArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $this->em->persist($ltTranslation);
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}
