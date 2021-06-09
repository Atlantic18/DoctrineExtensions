<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Entity\Article;
use Blameable\Fixture\Entity\Comment;
use Blameable\Fixture\Entity\Type;
use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Blameable\\Fixture\\Entity\\Article';
    public const COMMENT = 'Blameable\\Fixture\\Entity\\Comment';
    public const TYPE = 'Blameable\\Fixture\\Entity\\Type';

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testBlameable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        $this->assertTrue($sport instanceof Blameable);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->assertTrue($sportComment instanceof Blameable);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        $this->assertEquals('testuser', $sport->getCreated());
        $this->assertEquals('testuser', $sport->getUpdated());
        $this->assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        $this->assertEquals('testuser', $sportComment->getModified());
        $this->assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        $this->assertEquals('testuser', $sportComment->getClosed());

        $this->assertEquals('testuser', $sport->getPublished());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals('myuser', $sport->getCreated());
        $this->assertEquals('myuser', $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished('myuser');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        $this->assertEquals('myuser', $sport->getPublished());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        ];
    }
}
