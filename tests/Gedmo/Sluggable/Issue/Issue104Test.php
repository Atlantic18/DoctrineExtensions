<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue104\Car;
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
class Issue104Test extends BaseTestCaseORM
{
    public const CAR = 'Sluggable\\Fixture\\Issue104\\Car';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function shouldThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->getMockSqliteEntityManager($evm);

        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CAR,
        ];
    }
}
