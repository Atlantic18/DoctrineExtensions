<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Entity\UsingTrait;
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
class TraitUsageTest extends BaseTestCaseORM
{
    public const TARGET = 'Blameable\\Fixture\\Entity\\UsingTrait';

    protected function setUp(): void
    {
        parent::setUp();

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');
        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldTimestampUsingTrait()
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertNotNull($sport->getCreatedBy());
        $this->assertNotNull($sport->getUpdatedBy());
    }

    /**
     * @test
     */
    public function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait();
        $this->assertInstanceOf(self::TARGET, $sport->setCreatedBy('myuser'));
        $this->assertInstanceOf(self::TARGET, $sport->setUpdatedBy('myuser'));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
