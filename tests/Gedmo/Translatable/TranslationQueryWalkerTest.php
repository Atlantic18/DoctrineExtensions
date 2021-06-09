<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationQueryWalkerTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Translatable\\Fixture\\Article';
    public const COMMENT = 'Translatable\\Fixture\\Comment';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    public const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    public function shouldHandleQueryCache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $this->em->getConfiguration()->setQueryCacheImpl($cache);
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);

        $q2 = clone $q;
        $q2->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
    }

    /**
     * @test
     */
    public function subselectByTranslatedField()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $subSelect = 'SELECT a2.title FROM '.self::ARTICLE.' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.title IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(2, $result);
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
    }

    /**
     * @test
     */
    public function subselectStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $subSelect = 'SELECT a2.id FROM '.self::ARTICLE.' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.id IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(2, $result);
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
    }

    /**
     * @test
     */
    public function joinedWithStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c WITH c.subject LIKE :lookup';
        $dql .= ' WHERE a.title LIKE :filter';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('lookup', '%goo%');
        $q->setParameter('filter', 'Foo%');
        $result = $q->getArrayResult();

        $this->assertCount(1, $result);
        $this->assertEquals('Food', $result[0]['title']);
        $comments = $result[0]['comments'];
        $this->assertCount(1, $comments);
        $this->assertEquals('good', $comments[0]['subject']);
    }

    /**
     * @test
     */
    public function shouldSelectWithTranslationFallbackOnSimpleObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\SimpleObjectHydrator'
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // simple object hydration
        $this->startQueryLog();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('', $result[0]->getContent());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('about food', $result[0]->getContent());
    }

    /**
     * @test
     */
    public function selectWithTranslationFallbackOnArrayHydration()
    {
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $this->startQueryLog();
        $result = $q->getArrayResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]['title']);
        $this->assertEquals('', $result[0]['content']);

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getArrayResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]['title']);
        $this->assertEquals('about food', $result[0]['content']);
    }

    /**
     * @test
     */
    public function selectWithOptionalFallbackOnSimpleObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\SimpleObjectHydrator'
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // simple object hydration
        $this->startQueryLog();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('John Doe', $result[0]->getAuthor()); // optional fallback is true,  force fallback
        $this->assertEquals(0, $result[0]->getViews());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('John Doe', $result[0]->getAuthor());
        $this->assertEquals(0, $result[0]->getViews()); // optional fallback is false,  thus no translation required
    }

    /**
     * @test
     */
    public function shouldBeAbleToUseInnerJoinStrategyForTranslations()
    {
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_INNER_JOIN, true);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $result = $q->getArrayResult();
        $this->assertCount(0, $result);
    }

    /**
     * referres to issue #755
     *
     * @test
     */
    public function shouldBeAbleToOverrideTranslationFallbackByHint()
    {
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $this->translatableListener->setTranslationFallback(false);

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'undefined');
        $q->setHint(TranslatableListener::HINT_FALLBACK, true);

        // array hydration
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $this->assertEquals('Food', $result[0]['title']);

        // fallback false hint
        $q->setHint(TranslatableListener::HINT_FALLBACK, false);

        // array hydration
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $this->assertEquals(null, $result[0]['title']);
    }

    /**
     * @test
     */
    public function shouldBeAbleToOverrideTranslatableLocale()
    {
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt_lt');

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $this->assertEquals('Maistas', $result[0]['title']);
    }

    /**
     * @test
     */
    public function shouldSelectWithTranslationFallbackOnObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // object hydration
        $this->startQueryLog();
        $result = $q->getResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('', $result[0]->getContent());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('about food', $result[0]->getContent());

        // test fallback hint
        $this->translatableListener->setTranslationFallback(false);
        $q->setHint(TranslatableListener::HINT_FALLBACK, 1);

        $result = $q->getResult();
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('about food', $result[0]->getContent());

        // test fallback hint
        $this->translatableListener->setTranslationFallback(true);
        $q->setHint(TranslatableListener::HINT_FALLBACK, 0);

        $result = $q->getResult();
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('', $result[0]->getContent());
    }

    /**
     * @test
     */
    public function shouldSelectCountStatement()
    {
        $dql = 'SELECT COUNT(a) FROM '.self::ARTICLE.' a';
        $dql .= ' WHERE a.title LIKE :title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Foo%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(1, $result);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(1, $result);

        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(0, $result);
    }

    /**
     * @test
     */
    public function shouldSelectOrderedJoinedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        $this->populateMore();
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(4, $result);
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
        $this->assertEquals('Food', $result[2]['title']);
        $this->assertEquals('Woman', $result[3]['title']);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertCount(4, $result);
        $this->assertEquals('Alfabetas', $result[0]['title']);
        $this->assertEquals('Kopustai', $result[1]['title']);
        $this->assertEquals('Maistas', $result[2]['title']);
        $this->assertEquals('Moteris', $result[3]['title']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertCount(4, $result);
        $this->assertEquals('Alfabet', $result[0]->getTitle());
        $this->assertEquals('Cabbages', $result[1]->getTitle());
        $this->assertEquals('Food', $result[2]->getTitle());
        $this->assertEquals('Woman', $result[3]->getTitle());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertCount(4, $result);
        $this->assertEquals('Alfabetas', $result[0]->getTitle());
        $this->assertEquals('Kopustai', $result[1]->getTitle());
        $this->assertEquals('Maistas', $result[2]->getTitle());
        $this->assertEquals('Moteris', $result[3]->getTitle());
    }

    /**
     * @test
     */
    public function shouldSelectOrderedByTranslatableInteger()
    {
        // Given
        $this->populateMore();
        $dql = 'SELECT a.title, a.views FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.views';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // Test original
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        array_walk($result, function ($value, $key) use (&$result) {
            // Make each record be a "Title - Views" string
            $result[$key] = implode(' - ', $value);
        });
        $this->assertEquals(
            ['Alfabet - 1', 'Food - 99', 'Cabbages - 2222', 'Woman - 3333'], $result,
            'Original of localizible integers should be sorted numerically'
        );

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        array_walk($result, function ($value, $key) use (&$result) {
            // Make each record be a "Title - Views" string
            $result[$key] = implode(' - ', $value);
        });
        $this->assertEquals(
            ['Moteris - 33', 'Alfabetas - 111', 'Maistas - 999', 'Kopustai - 22222'], $result,
            'Localized integers should be sorted numerically'
        );
    }

    /**
     * @test
     */
    public function shouldSelectSecondJoinedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c ORDER BY c.id ASC';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(6, $food);
        $this->assertEquals('Food', $food['title']);
        $this->assertEquals('about food', $food['content']);
        $comments = $food['comments'];
        $this->assertCount(2, $comments);
        $good = $comments[0];
        $this->assertCount(3, $good);
        $this->assertEquals('good', $good['subject']);
        $this->assertEquals('food is good', $good['message']);
        $bad = $comments[1];
        $this->assertCount(3, $bad);
        $this->assertEquals('bad', $bad['subject']);
        $this->assertEquals('food is bad', $bad['message']);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(6, $food);
        $this->assertEquals('Maistas', $food['title']);
        $this->assertEquals('apie maista', $food['content']);
        $comments = $food['comments'];
        $this->assertCount(2, $comments);
        $good = $comments[0];
        $this->assertCount(3, $good);
        $this->assertEquals('geras', $good['subject']);
        $this->assertEquals('maistas yra geras', $good['message']);
        $bad = $comments[1];
        $this->assertCount(3, $bad);
        $this->assertEquals('blogas', $bad['subject']);
        $this->assertEquals('maistas yra blogas', $bad['message']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertEquals('Food', $food->getTitle());
        $this->assertEquals('about food', $food->getContent());
        $comments = $food->getComments();
        $this->assertCount(2, $comments);
        $good = $comments[0];
        $this->assertEquals('good', $good->getSubject());
        $this->assertEquals('food is good', $good->getMessage());
        $bad = $comments[1];
        $this->assertEquals('bad', $bad->getSubject());
        $this->assertEquals('food is bad', $bad->getMessage());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertEquals('Maistas', $food->getTitle());
        $this->assertEquals('apie maista', $food->getContent());
        $comments = $food->getComments();
        $this->assertCount(2, $comments);
        $good = $comments[0];
        $this->assertInstanceOf(self::COMMENT, $good);
        $this->assertEquals('geras', $good->getSubject());
        $this->assertEquals('maistas yra geras', $good->getMessage());
        $bad = $comments[1];
        $this->assertEquals('blogas', $bad->getSubject());
        $this->assertEquals('maistas yra blogas', $bad->getMessage());
    }

    /**
     * @test
     */
    public function shouldSelectSinglePartializedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        $dql = 'SELECT a.title FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(1, $food);
        $this->assertEquals('Food', $food['title']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(1, $food);
        $this->assertEquals('Maistas', $food['title']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(1, $food);
        $this->assertEquals('Food', $food['title']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(1, $food);
        $this->assertEquals('Maistas', $food['title']);
    }

    /**
     * @test
     */
    public function shouldSelectSingleComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(5, $food);
        $this->assertEquals('Food', $food['title']);
        $this->assertEquals('about food', $food['content']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertCount(5, $food);
        $this->assertEquals('Maistas', $food['title']);
        $this->assertEquals('apie maista', $food['content']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertInstanceOf(self::ARTICLE, $food);
        $this->assertEquals('Food', $food->getTitle());
        $this->assertEquals('about food', $food->getContent());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertCount(1, $result);
        $food = $result[0];
        $this->assertEquals('Maistas', $food->getTitle());
        $this->assertEquals('apie maista', $food->getContent());
    }

    /**
     * @test
     * @group testSelectWithUnmappedField
     */
    public function shouldSelectWithUnmappedField()
    {
        $dql = 'SELECT a.title, count(a.id) AS num FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertCount(1, $result);
        $this->assertEquals('Food', $result[0]['title']);
        $this->assertEquals(1, $result[0]['num']);
    }

    /**
     * @test
     */
    public function shouldPreserveSkipOnLoadForSimpleHydrator()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\SimpleObjectHydrator'
        );
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setSkipOnLoad(true);
        $q->getResult(Query::HYDRATE_SIMPLEOBJECT);

        $this->assertTrue($this->translatableListener->isSkipOnLoad());
    }

    /**
     * @test
     */
    public function shouldPreserveSkipOnLoadForObjectHydrator()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
        );
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setSkipOnLoad(true);
        $q->getResult(Query::HYDRATE_OBJECT);

        $this->assertTrue($this->translatableListener->isSkipOnLoad());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
        ];
    }

    private function populateMore()
    {
        $repo = $this->em->getRepository(self::ARTICLE);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $this->translatableListener->setTranslatableLocale('en_us');
        $alfabet = new Article();
        $alfabet->setTitle('Alfabet');
        $alfabet->setContent('hey wtf!');
        $alfabet->setViews(1);

        $woman = new Article();
        $woman->setTitle('Woman');
        $woman->setContent('i like them');
        $woman->setViews(3333);

        $cabbages = new Article();
        $cabbages->setTitle('Cabbages');
        $cabbages->setContent('where went the woman?');
        $cabbages->setViews(2222);

        $this->em->persist($alfabet);
        $this->em->persist($woman);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $alfabet = $repo->find(2);
        $alfabet->setTitle('Alfabetas');
        $alfabet->setContent('ei wtf!');
        $alfabet->setViews(111);

        $woman = $repo->find(3);
        $woman->setTitle('Moteris');
        $woman->setContent('as megstu jas');
        $woman->setViews(33);

        $cabbages = $repo->find(4);
        $cabbages->setTitle('Kopustai');
        $cabbages->setContent('kur dingo moteris?');
        $cabbages->setViews(22222);

        $this->em->persist($alfabet);
        $this->em->persist($woman);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::ARTICLE);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $food = new Article();
        $food->setTitle('Food');
        $food->setContent('about food');
        $food->setAuthor('John Doe');
        $food->setViews(99);

        $goodFood = new Comment();
        $goodFood->setArticle($food);
        $goodFood->setMessage('food is good');
        $goodFood->setSubject('good');

        $badFood = new Comment();
        $badFood->setArticle($food);
        $badFood->setMessage('food is bad');
        $badFood->setSubject('bad');

        $this->em->persist($food);
        $this->em->persist($goodFood);
        $this->em->persist($badFood);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $food = $repo->find(1);
        $food->setTitle('Maistas');
        $food->setContent('apie maista');
        $food->setViews(999);

        $goodFood = $commentRepo->find(1);
        $goodFood->setArticle($food);
        $goodFood->setMessage('maistas yra geras');
        $goodFood->setSubject('geras');

        $badFood = $commentRepo->find(2);
        $badFood->setArticle($food);
        $badFood->setMessage('maistas yra blogas');
        $badFood->setSubject('blogas');

        $this->em->persist($food);
        $this->em->persist($goodFood);
        $this->em->persist($badFood);
        $this->em->flush();
        $this->em->clear();
    }
}
