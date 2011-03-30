<?php

namespace Gedmo\Loggable;

use Doctrine\Common\Util\Debug,
    Loggable\Fixture\Entity\Article,
    Loggable\Fixture\Entity\RelatedArticle,
    Loggable\Fixture\Entity\Comment;
    
/**
 * These are tests for loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableEntityTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS_ARTICLE = 'Loggable\Fixture\Entity\Article';
    const TEST_ENTITY_CLASS_COMMENT = 'Loggable\Fixture\Entity\Comment';
    const TEST_ENTITY_CLASS_RELATED_ARTICLE = 'Loggable\Fixture\Entity\RelatedArticle';
    const TEST_ENTITY_CLASS_LOG_COMMENT = 'Loggable\Fixture\Entity\Log\Comment';

    private $articleId;
    private $LoggableListener;
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Loggable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $this->LoggableListener = new LoggableListener();
        $this->LoggableListener->setUsername('jules');
        $evm->addEventSubscriber($this->LoggableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_COMMENT),
            $this->em->getClassMetadata('Gedmo\Loggable\Entity\LogEntry'),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_LOG_COMMENT),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_RELATED_ARTICLE)
        ));
    }

    public function testLoggable()
    {
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $articleRepo = $this->em->getRepository(self::TEST_ENTITY_CLASS_ARTICLE);
        $this->assertEquals(0, count($logRepo->findAll()));

        $art0 = new Article();
        $art0->setTitle('Title');
        
        $this->em->persist($art0);
        $this->em->flush();

        $log = $logRepo->findOneByObjectId($art0->getId());
        
        $this->assertNotEquals(null, $log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertEquals(1, count($data));
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');
        
        // test update
        $article = $articleRepo->findOneByTitle('Title');
        
        $article->setTitle('New');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $log = $logRepo->findOneBy(array('version' => 2, 'objectId' => $article->getId()));
        $this->assertEquals('update', $log->getAction());
        
        // test delete
        $article = $articleRepo->findOneByTitle('New');
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();
        
        $log = $logRepo->findOneBy(array('version' => 3, 'objectId' => 1));
        $this->assertEquals('remove', $log->getAction());
        $this->assertEquals(null, $log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->em->getRepository(self::TEST_ENTITY_CLASS_LOG_COMMENT);
        $commentRepo = $this->em->getRepository(self::TEST_ENTITY_CLASS_COMMENT);
        
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
        $this->assertEquals(6, count($logEntries));
        $latest = $logEntries[0];
        $this->assertEquals('update', $latest->getAction());
    }
    
    private function populate()
    {
        $article = new RelatedArticle;
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');
        
        $comment = new Comment;
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
        
        $article2 = new RelatedArticle;
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