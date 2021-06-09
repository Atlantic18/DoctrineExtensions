<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventManager;
use Loggable\Fixture\Document\Article;
use Loggable\Fixture\Document\Author;
use Loggable\Fixture\Document\Comment;
use Loggable\Fixture\Document\RelatedArticle;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for loggable behavior
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Loggable\\Fixture\\Document\\Article';
    public const COMMENT = 'Loggable\\Fixture\\Document\\Comment';
    public const RELATED_ARTICLE = 'Loggable\\Fixture\\Document\\RelatedArticle';
    public const COMMENT_LOG = 'Loggable\\Fixture\\Document\\Log\\Comment';

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setUsername('jules');
        $evm->addEventSubscriber($loggableListener);

        $this->getMockDocumentManager($evm);
    }

    public function testLogGeneration()
    {
        $logRepo = $this->dm->getRepository('Gedmo\\Loggable\\Document\\LogEntry');
        $articleRepo = $this->dm->getRepository(self::ARTICLE);
        $this->assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0->setAuthor($author);

        $this->dm->persist($art0);
        $this->dm->flush();

        $log = $logRepo->findOneBy(['objectId' => $art0->getId()]);

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');
        $this->assertArrayHasKey('author', $data);
        $this->assertEquals($data['author'], ['name' => 'John Doe', 'email' => 'john@doe.com']);

        // test update
        $article = $articleRepo->findOneBy(['title' => 'Title']);
        $article->setTitle('New');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $article->getId()]);
        $this->assertEquals('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneBy(['title' => 'New']);
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => $article->getId()]);
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->dm->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->dm->getRepository(self::COMMENT);

        $comment = $commentRepo->findOneBy(['message' => 'm-v5']);
        $commentId = $comment->getId();
        $this->assertEquals('m-v5', $comment->getMessage());
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('a2-t-v1', $comment->getArticle()->getTitle());
        $this->assertEquals('Jane Doe', $comment->getAuthor()->getName());
        $this->assertEquals('jane@doe.com', $comment->getAuthor()->getEmail());

        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals('a1-t-v1', $comment->getArticle()->getTitle());
        $this->assertEquals('John Doe', $comment->getAuthor()->getName());
        $this->assertEquals('john@doe.com', $comment->getAuthor()->getEmail());
        $this->dm->persist($comment);
        $this->dm->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = array_shift($logEntries);
        $this->assertEquals('update', $latest->getAction());
    }

    private function populate()
    {
        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuthor($author);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');

        $this->dm->persist($article);
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setMessage('m-v2');
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setSubject('s-v3');
        $this->dm->persist($comment);
        $this->dm->flush();

        $article2 = new RelatedArticle();
        $article2->setTitle('a2-t-v1');
        $article2->setContent('a2-c-v1');

        $author2 = new Author();
        $author2->setName('Jane Doe');
        $author2->setEmail('jane@doe.com');

        $comment->setAuthor($author2);
        $comment->setArticle($article2);
        $this->dm->persist($article2);
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setMessage('m-v5');
        $this->dm->persist($comment);
        $this->dm->flush();
        $this->dm->clear();
    }
}
