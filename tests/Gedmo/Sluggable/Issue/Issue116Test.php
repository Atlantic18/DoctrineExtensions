<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Sluggable\Fixture\Issue116\Country;
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
class Issue116Test extends BaseTestCaseORM
{
    public const TARGET = 'Sluggable\\Fixture\\Issue116\\Country';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    protected function getMetadataDriverImplementation()
    {
        $chain = new DriverChain();
        $chain->addDriver(
            new YamlDriver([__DIR__.'/../Fixture/Issue116/Mapping']),
            'Sluggable\Fixture\Issue116'
        );

        return $chain;
    }

    public function testSlugGeneration()
    {
        $country = new Country();
        $country->setOriginalName('New Zealand');

        $this->em->persist($country);
        $this->em->flush();

        $this->assertEquals('new-zealand', $country->getAlias());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
