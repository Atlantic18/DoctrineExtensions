<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;
use Translatable\Fixture\Sport;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Translatable\\Fixture\\Article';
    public const SPORT = 'Translatable\\Fixture\\Sport';
    public const COMMENT = 'Translatable\\Fixture\\Comment';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $articleId;
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
    }

    /**
     * @test
     */
    public function shouldUpdateTranslationInDefaultLocaleIssue751()
    {
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $repo = $this->em->getRepository(self::ARTICLE);

        $entity = new Article();
        $entity->setTranslatableLocale('de');
        $entity->setTitle('test');
        $this->em->persist($entity);
        $this->em->flush();

        $entity->setTranslatableLocale('de');
        $entity->setTitle('test!');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        // this will force it to find translation in "en" locale, since listener has "en" set
        // and since default locale is "en" current translation will be "test"
        // setting title to "test" will not even persist entity, since there is no changeset
        $entity = $repo->findOneBy(['id' => $entity->getId()]);
        $entity->setTranslatableLocale('de');
        $entity->setTitle('test');
        $this->em->persist($entity);
        $this->em->flush();

        $this->em->clear();
        $entity = $repo->findOneBy(['id' => $entity->getId()]);
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($entity);
        $this->assertArrayHasKey('de', $translations);
        $this->assertSame('test!', $translations['de']['title']); // de translation was not updated, no changeset
        $this->assertSame('test', $entity->getTitle()); // obviously "test" a default en translation
    }

    /**
     * @test
     */
    public function shouldPersistDefaultLocaleTranslationIfRequired()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);

        $article = new Article();
        $article->setTitle('title in en');
        $article->setContent('content in en');

        $this->em->persist($article);
        $this->em->flush();

        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('en_us', $translations);
    }

    /**
     * @test
     */
    public function shouldGenerateTranslations()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable);

        $translations = $repo->findTranslations($article);
        $this->assertCount(0, $translations);

        $comments = $article->getComments();
        $this->assertCount(2, $comments);
        foreach ($comments as $num => $comment) {
            $translations = $repo->findTranslations($comment);

            $this->assertCount(0, $translations);
        }
        // test default locale
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $qb = $this->em->createQueryBuilder();
        $qb->select('art')
            ->from(self::ARTICLE, 'art')
            ->where('art.id = :id');
        $q = $qb->getQuery();
        $result = $q->execute(
            ['id' => $article->getId()],
            \Doctrine\ORM\Query::HYDRATE_ARRAY
        );
        $this->assertCount(1, $result);
        $this->assertEquals('title in en', $result[0]['title']);
        $this->assertEquals('content in en', $result[0]['content']);

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        // test second translations
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            $comment->setTranslatableLocale('de_de');
            $comment->setSubject("subject{$number} in de");
            $comment->setMessage("message{$number} in de");
            $this->em->persist($comment);
        }
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        $comments = $article->getComments();
        $this->assertCount(2, $comments);
        foreach ($comments as $comment) {
            $translations = $repo->findTranslations($comment);

            $this->assertCount(1, $translations);
            $this->assertArrayHasKey('de_de', $translations);

            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            $this->assertArrayHasKey('subject', $translations['de_de']);
            $expected = "subject{$number} in de";
            $this->assertEquals($expected, $translations['de_de']['subject']);

            $this->assertArrayHasKey('message', $translations['de_de']);
            $expected = "message{$number} in de";
            $this->assertEquals($expected, $translations['de_de']['message']);
        }

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertEquals('title in en', $article->getTitle());
        $this->assertEquals('content in en', $article->getContent());

        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());

            $this->assertEquals("subject{$number} in en", $comment->getSubject());
            $this->assertEquals("message{$number} in en", $comment->getMessage());
        }
        // test deletion
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->em->remove($article);
        $this->em->flush();

        $translations = $repo->findTranslations($article);
        $this->assertCount(0, $translations);
    }

    /**
     * @test
     */
    public function shouldSolveTranslationFallbackGithubIssue9()
    {
        $this->populate();
        $this->translatableListener->setTranslationFallback(false);
        $this->translatableListener->setTranslatableLocale('ru_RU');

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertFalse((bool) $article->getTitle());
        $this->assertFalse((bool) $article->getContent());

        foreach ($article->getComments() as $comment) {
            $this->assertFalse((bool) $comment->getSubject());
            $this->assertFalse((bool) $comment->getMessage());
        }
        $this->em->clear();
        $this->translatableListener->setTranslationFallback(true);
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        $this->assertEquals('title in en', $article->getTitle());
        $this->assertEquals('content in en', $article->getContent());
    }

    /**
     * @test
     */
    public function shouldSolveGithubIssue64()
    {
        $judo = new Sport();
        $judo->setTitle('Judo');
        $judo->setDescription('Whatever');

        $this->em->persist($judo);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de_de');

        $judo->setTitle('Judo');
        $judo->setDescription('Something in changeset');
        $this->em->persist($judo);
        $this->em->flush();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($judo);
        $this->assertCount(1, $translations);

        // now without any changeset
        $this->translatableListener->setTranslatableLocale('ru_ru');

        $judo->setTitle('Judo');
        $this->em->persist($judo);
        $this->em->flush();

        // this will not add additional translation, because it cannot be tracked
        // without anything in changeset
        $translations = $repo->findTranslations($judo);
        $this->assertCount(1, $translations);
    }

    /**
     * @test
     */
    public function shouldRespectFallbackOption()
    {
        $article = new Article();
        $article->setTitle('Euro2012');
        $article->setAuthor('Shevchenko');
        $article->setViews(10);

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('ua_UA');
        $this->translatableListener->setTranslationFallback(true);
        $article = $this->em->find(self::ARTICLE, $article->getId());

        $this->assertEquals('Euro2012', $article->getTitle());
        $this->assertEquals('Shevchenko', $article->getAuthor());
        $this->assertEmpty($article->getViews());

        $this->em->clear();
        $this->translatableListener->setTranslationFallback(false);
        $article = $this->em->find(self::ARTICLE, $article->getId());
        $this->assertEmpty($article->getTitle());
        $this->assertEquals('Shevchenko', $article->getAuthor());
        $this->assertEmpty($article->getViews());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
            self::SPORT,
        ];
    }

    private function populate()
    {
        $article = new Article();
        $article->setTitle('title in en');
        $article->setContent('content in en');

        $comment1 = new Comment();
        $comment1->setSubject('subject1 in en');
        $comment1->setMessage('message1 in en');

        $comment2 = new Comment();
        $comment2->setSubject('subject2 in en');
        $comment2->setMessage('message2 in en');

        $article->addComment($comment1);
        $article->addComment($comment2);

        $this->em->persist($article);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->articleId = $article->getId();
        $this->em->clear();
    }
}
