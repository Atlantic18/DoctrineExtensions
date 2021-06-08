<?php

namespace Gedmo\Sluggable;

use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableMappingTest extends \PHPUnit\Framework\TestCase
{
    const TEST_YAML_ENTITY_CLASS = 'Mapping\Fixture\Yaml\Category';
    const SLUGGABLE = 'Mapping\Fixture\Sluggable';
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

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableUsingYamlDriver()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('slugs', $config);
        $this->assertArrayHasKey('slug', $config['slugs']);
        $this->assertEquals('slug', $config['slugs']['slug']['slug']);
        $this->assertArrayHasKey('fields', $config['slugs']['slug']);
        $this->assertCount(1, $config['slugs']['slug']['fields']);
        $this->assertEquals('title', $config['slugs']['slug']['fields'][0]);

        $this->assertArrayHasKey('style', $config['slugs']['slug']);
        $this->assertEquals('camel', $config['slugs']['slug']['style']);
        $this->assertArrayHasKey('separator', $config['slugs']['slug']);
        $this->assertEquals('_', $config['slugs']['slug']['separator']);
        $this->assertArrayHasKey('unique', $config['slugs']['slug']);
        $this->assertTrue($config['slugs']['slug']['unique']);
        $this->assertArrayHasKey('updatable', $config['slugs']['slug']);
        $this->assertTrue($config['slugs']['slug']['updatable']);

        $this->assertArrayHasKey('handlers', $config['slugs']['slug']);
        $handlers = $config['slugs']['slug']['handlers'];
        $this->assertEquals(2, is_array($handlers) || $handlers instanceof \Countable ? count($handlers) : 0);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        $this->assertEquals(2, is_array($first) || $first instanceof \Countable ? count($first) : 0);
        $this->assertArrayHasKey('parentRelationField', $first);
        $this->assertArrayHasKey('separator', $first);
        $this->assertEquals('parent', $first['parentRelationField']);
        $this->assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        $this->assertEquals(3, is_array($second) || $second instanceof \Countable ? count($second) : 0);
        $this->assertArrayHasKey('relationField', $second);
        $this->assertArrayHasKey('relationSlugField', $second);
        $this->assertArrayHasKey('separator', $second);
        $this->assertEquals('parent', $second['relationField']);
        $this->assertEquals('slug', $second['relationSlugField']);
        $this->assertEquals('/', $second['separator']);
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableUsingAnnotationDriver()
    {
        $meta = $this->em->getClassMetadata(self::SLUGGABLE);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::SLUGGABLE,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('handlers', $config['slugs']['slug']);
        $handlers = $config['slugs']['slug']['handlers'];
        $this->assertEquals(2, is_array($handlers) || $handlers instanceof \Countable ? count($handlers) : 0);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        $this->assertEquals(2, is_array($first) || $first instanceof \Countable ? count($first) : 0);
        $this->assertArrayHasKey('parentRelationField', $first);
        $this->assertArrayHasKey('separator', $first);
        $this->assertEquals('parent', $first['parentRelationField']);
        $this->assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        $this->assertEquals(3, is_array($second) || $second instanceof \Countable ? count($second) : 0);
        $this->assertArrayHasKey('relationField', $second);
        $this->assertArrayHasKey('relationSlugField', $second);
        $this->assertArrayHasKey('separator', $second);
        $this->assertEquals('user', $second['relationField']);
        $this->assertEquals('slug', $second['relationSlugField']);
        $this->assertEquals('/', $second['separator']);
    }
}
