<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Identifier;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableIdentifierTest extends BaseTestCaseORM
{
    public const TARGET = 'Sluggable\\Fixture\\Identifier';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldBePossibleToSlugIdentifiers()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        $this->assertEquals('sport', $sport->getId());
    }

    /**
     * @test
     */
    public function shouldPersistMultipleNonConflictingIdentifierSlugs()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier();
        $sport2->setTitle('Sport');
        $this->em->persist($sport2);
        $this->em->flush();

        $this->assertEquals('sport', $sport->getId());
        $this->assertEquals('sport_1', $sport2->getId());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
