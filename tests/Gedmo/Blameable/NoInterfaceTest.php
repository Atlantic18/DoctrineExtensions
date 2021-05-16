<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Entity\WithoutInterface;
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
class NoInterfaceTest extends BaseTestCaseORM
{
    public const FIXTURE = 'Blameable\\Fixture\\Entity\\WithoutInterface';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $blameableListener = new BlameableListener();
        $blameableListener->setUserValue('testuser');
        $evm->addEventSubscriber($blameableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testBlameableNoInterface()
    {
        $test = new WithoutInterface();
        $test->setTitle('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneBy(['title' => 'Test']);
        $this->assertEquals('testuser', $test->getCreated());
        $this->assertEquals('testuser', $test->getUpdated());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::FIXTURE,
        ];
    }
}
