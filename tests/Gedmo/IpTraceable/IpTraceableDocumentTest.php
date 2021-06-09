<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use IpTraceable\Fixture\Document\Article;
use IpTraceable\Fixture\Document\Type;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for IpTraceable behavior ODM implementation
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableDocumentTest extends BaseTestCaseMongoODM
{
    public const TEST_IP = '34.234.1.10';

    public const ARTICLE = 'IpTraceable\Fixture\Document\Article';
    public const TYPE = 'IpTraceable\Fixture\Document\Type';

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new IpTraceableListener();
        $listener->setIpValue(self::TEST_IP);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testIpTraceable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'IpTraceable Article']);

        $this->assertEquals(self::TEST_IP, $article->getCreated());
        $this->assertEquals(self::TEST_IP, $article->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'IpTraceable Article']);

        $this->assertEquals(self::TEST_IP, $article->getPublished());
        $this->assertEquals(self::TEST_IP, $article->getCreated());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_IP);
        $sport->setUpdated(self::TEST_IP);

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals(self::TEST_IP, (string) $sport->getCreated());
        $this->assertEquals(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals(self::TEST_IP, $sport->getPublished());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('IpTraceable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
