<?php

namespace Gedmo\Loggable;

use Doctrine\Common\EventManager;
use Loggable\Fixture\Entity\Address;
use Loggable\Fixture\Entity\Article;
use Loggable\Fixture\Entity\Comment;
use Loggable\Fixture\Entity\Composite;
use Loggable\Fixture\Entity\CompositeRelation;
use Loggable\Fixture\Entity\Geo;
use Loggable\Fixture\Entity\GeoLocation;
use Loggable\Fixture\Entity\RelatedArticle;
use Tool\BaseTestCaseORM;

/**
 * These are tests for loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableEntityTest extends BaseTestCaseORM
{
    const ARTICLE = 'Loggable\Fixture\Entity\Article';
    const COMMENT = 'Loggable\Fixture\Entity\Comment';
    const COMPOSITE = 'Loggable\Fixture\Entity\Composite';
    const COMPOSITE_RELATION = 'Loggable\Fixture\Entity\CompositeRelation';
    const RELATED_ARTICLE = 'Loggable\Fixture\Entity\RelatedArticle';
    const COMMENT_LOG = 'Loggable\Fixture\Entity\Log\Comment';

    private $articleId;
    private $LoggableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->LoggableListener = new LoggableListener();
        $this->LoggableListener->setUsername('jules');
        $evm->addEventSubscriber($this->LoggableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleClonedEntity()
    {
        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $art1 = clone $art0;
        $art1->setTitle('Cloned');
        $this->em->persist($art1);
        $this->em->flush();

        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $logs = $logRepo->findAll();
        $this->assertSame(2, count($logs));
        $this->assertSame('create', $logs[0]->getAction());
        $this->assertSame('create', $logs[1]->getAction());
        $this->assertTrue($logs[0]->getObjectId() !== $logs[1]->getObjectId());
    }

    public function testLoggable()
    {
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $articleRepo = $this->em->getRepository(self::ARTICLE);
        $this->assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $log = $logRepo->findOneBy(['objectId' => $art0->getId()]);

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');

        // test update
        $article = $articleRepo->findOneBy(['title' => 'Title']);

        $article->setTitle('New');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $article->getId()]);
        $this->assertEquals('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneBy(['title' => 'New']);
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => 1]);
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->em->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $comment = $commentRepo->find(1);
        $this->assertEquals('m-v5', $comment->getMessage());
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals(2, $comment->getArticle()->getId());

        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals(1, $comment->getArticle()->getId());
        $this->em->persist($comment);
        $this->em->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = $logEntries[0];
        $this->assertEquals('update', $latest->getAction());
    }

    public function testLogEmbedded()
    {
        $address = $this->populateEmbedded();

        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');

        $logEntries = $logRepo->getLogEntries($address);

        $this->assertCount(4, $logEntries);
        $this->assertCount(1, $logEntries[0]->getData());
        $this->assertCount(2, $logEntries[1]->getData());
        $this->assertCount(3, $logEntries[2]->getData());
        $this->assertCount(5, $logEntries[3]->getData());
    }

    public function testComposite()
    {
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $compositeRepo = $this->em->getRepository(self::COMPOSITE);
        $this->assertCount(0, $logRepo->findAll());

        $cmp0 = new Composite(1, 2);
        $cmp0->setTitle('Title2');

        $this->em->persist($cmp0);
        $this->em->flush();

        $cmpId = sprintf('%s %s', 1, 2);

        $log = $logRepo->findOneByObjectId($cmpId);

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($cmp0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title2');

        // test update
        $composite = $compositeRepo->findOneByTitle('Title2');

        $composite->setTitle('New');
        $this->em->persist($composite);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $cmpId]);
        $this->assertEquals('update', $log->getAction());

        // test delete
        $composite = $compositeRepo->findOneByTitle('New');
        $this->em->remove($composite);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => $cmpId]);
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    public function testCompositeRelation()
    {
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $compositeRepo = $this->em->getRepository(self::COMPOSITE_RELATION);
        $this->assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title0');
        $art1 = new Article();
        $art1->setTitle('Title1');
        $cmp0 = new CompositeRelation($art0, $art1);
        $cmp0->setTitle('Title2');

        $this->em->persist($art0);
        $this->em->persist($art1);
        $this->em->persist($cmp0);
        $this->em->flush();

        $cmpId = sprintf('%s %s', $art0->getId(), $art1->getId());

        $log = $logRepo->findOneByObjectId($cmpId);

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($cmp0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title2');

        // test update
        $composite = $compositeRepo->findOneByTitle('Title2');

        $composite->setTitle('New');
        $this->em->persist($composite);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $cmpId]);
        $this->assertEquals('update', $log->getAction());

        // test delete
        $composite = $compositeRepo->findOneByTitle('New');
        $this->em->remove($composite);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => $cmpId]);
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::COMMENT_LOG,
            self::RELATED_ARTICLE,
            self::COMPOSITE,
            self::COMPOSITE_RELATION,
            'Gedmo\Loggable\Entity\LogEntry',
            'Loggable\Fixture\Entity\Address',
            'Loggable\Fixture\Entity\Geo',
        ];
    }

    private function populateEmbedded()
    {
        $address = new Address();
        $address->setCity('city-v1');
        $address->setStreet('street-v1');

        $geo = new Geo(1.0000, 1.0000, new GeoLocation('Online'));

        $address->setGeo($geo);

        $this->em->persist($address);
        $this->em->flush();

        $geo2 = new Geo(2.0000, 2.0000, new GeoLocation('Offline'));
        $address->setGeo($geo2);

        $this->em->persist($address);
        $this->em->flush();

        $address->getGeo()->setLatitude(3.0000);
        $address->getGeo()->setLongitude(3.0000);

        $this->em->persist($address);
        $this->em->flush();

        $address->setStreet('street-v2');

        $this->em->persist($address);
        $this->em->flush();

        return $address;
    }

    private function populate()
    {
        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');

        $this->em->persist($article);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v2');
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setSubject('s-v3');
        $this->em->persist($comment);
        $this->em->flush();

        $article2 = new RelatedArticle();
        $article2->setTitle('a2-t-v1');
        $article2->setContent('a2-c-v1');

        $comment->setArticle($article2);
        $this->em->persist($article2);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v5');
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();
    }
}
