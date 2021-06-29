<?php

namespace Gedmo\Loggable;

use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableMappingTest extends \PHPUnit\Framework\TestCase
{
    const YAML_CATEGORY = 'Mapping\Fixture\Yaml\Category';
    const COMPOSITE = 'Mapping\Fixture\LoggableComposite';
    const COMPOSITE_RELATION = 'Mapping\Fixture\LoggableCompositeRelation';
    private $em;

    public function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver([__DIR__.'/Driver/Yaml']),
            'Mapping\Fixture\Yaml'
        );
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\\Mapping\\Annotation',
            VENDOR_PATH.'/../lib'
        );
        $chainDriverImpl->addDriver(
            new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader),
            'Mapping\Fixture'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new LoggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testLoggableMapping()
    {
        $meta = $this->em->getClassMetadata(self::YAML_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CATEGORY, 'Gedmo\Loggable');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);
        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\\Loggable\\Entity\\LogEntry', $config['logEntryClass']);
    }

    public function testLoggableCompositeMapping()
    {
        $meta = $this->em->getClassMetadata(self::COMPOSITE);

        $this->assertTrue(is_array($meta->identifier));
        $this->assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId(self::COMPOSITE, 'Gedmo\Loggable');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);
    }

    public function testLoggableCompositeRelationMapping()
    {
        $meta = $this->em->getClassMetadata(self::COMPOSITE_RELATION);

        $this->assertTrue(is_array($meta->identifier));
        $this->assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId(self::COMPOSITE_RELATION, 'Gedmo\Loggable');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);
    }
}
