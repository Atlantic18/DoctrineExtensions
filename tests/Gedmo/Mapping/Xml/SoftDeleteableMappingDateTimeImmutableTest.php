<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class SoftDeleteableMappingDateTimeImmutableTest extends BaseTestCaseOM
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
        if (!Type::hasType('datetime_immutable')) {
            $this->markTestSkipped('This test requires "date*_immutable" types to be defined, which are included with "doctrine/dbal:^2.6"');
        }
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
            'Mapping\Fixture\Xml\SoftDeleteableDateTimeImmutable',
            'Mapping\Fixture\SoftDeleteable',
        ], $chain);
    }

    public function testMetadataDateTimeImmutable()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\SoftDeleteableDateTimeImmutable');
        $config = $this->softDeleteable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('softDeleteable', $config);
        $this->assertTrue($config['softDeleteable']);
        $this->assertArrayHasKey('timeAware', $config);
        $this->assertFalse($config['timeAware']);
        $this->assertArrayHasKey('fieldName', $config);
        $this->assertSame('deletedAt', $config['fieldName']);
//        $this->assertArrayHasKey('type', $config);
//        $this->assertInstanceOf('DateTimeImmutable', Type::getType($config['type'])->convertToPHPValue('now', $this->em->getConnection()->getDatabasePlatform()));
    }
}
