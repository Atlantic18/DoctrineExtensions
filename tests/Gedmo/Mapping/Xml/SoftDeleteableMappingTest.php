<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\SoftDeleteable\SoftDeleteableListener
     */
    private $softDeleteable;

    public function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');
        $chain->addDriver($annotationDriver, 'Mapping\Fixture');

        $this->softDeleteable = new SoftDeleteableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->softDeleteable);

        $this->em = $this->getMockSqliteEntityManager([
            'Mapping\Fixture\Xml\SoftDeleteable',
            'Mapping\Fixture\SoftDeleteable',
        ], $chain);
    }

    public function testMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\SoftDeleteable');
        $config = $this->softDeleteable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('softDeleteable', $config);
        $this->assertTrue($config['softDeleteable']);
        $this->assertArrayHasKey('timeAware', $config);
        $this->assertFalse($config['timeAware']);
        $this->assertArrayHasKey('fieldName', $config);
        $this->assertEquals('deletedAt', $config['fieldName']);
//        $this->assertInstanceOf('DateTime', Type::getType($config['type'])->convertToPHPValue('now', $this->em->getConnection()->getDatabasePlatform()));
    }
}
