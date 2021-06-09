<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Timestampable\Fixture\Document\Article;
use Timestampable\Fixture\Document\Type;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for Timestampable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Timestampable\Fixture\Document\Article';
    public const TYPE = 'Timestampable\Fixture\Document\Type';

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testTimestampable()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Timestampable Article']);

        $date = new \DateTime();
        $now = time();
        $created = $article->getCreated()->getTimestamp();
        $this->assertTrue($created > $now - 5 && $created < $now + 5); // 5 seconds interval if lag
        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $article->getUpdated()->format('Y-m-d H:i')
        );

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'Timestampable Article']);
        $date = new \DateTime();
        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $article->getPublished()->format('Y-m-d H:i')
        );
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $created = strtotime('2000-01-01 12:00:00');
        $sport->setCreated($created);
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals(
            $created,
            $sport->getCreated()->getTimestamp()
        );
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @test
     */
    public function shouldHandleOnChangeWithBooleanValue()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Timestampable Article']);

        $this->assertNull($article->getReady());

        $article->setIsReady(true);
        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertNotNull($article->getReady());
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Timestampable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
