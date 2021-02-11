<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue1962\Bus;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue1962\Car;

/**
 * These are tests for Sluggable behavior
 *
 * @author Pierre-Yves CARIOU <cariou.p@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue1962Test extends BaseTestCaseORM
{
    const VEHICLE = 'Sluggable\\Fixture\\Issue1962\\Vehicle';
    const CAR = 'Sluggable\\Fixture\\Issue1962\\Car';
    const BUS = 'Sluggable\\Fixture\\Issue1962\\Bus';

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function testSlugGeneration()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->getMockSqliteEntityManager($evm);

        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $ratp = new Bus();
        $ratp->setDescription('Bus electrique qui arrive toujours à l\'heure');
        $ratp->setTitle('ratp');

        $this->em->persist($audi);
        $this->em->persist($ratp);
        $this->em->flush();

        $this->assertEquals('audi', $audi->getSlug());
        $this->assertEquals('ratp', $ratp->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR,
            self::BUS,
        );
    }
}
