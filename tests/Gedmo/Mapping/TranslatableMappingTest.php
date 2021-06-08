<?php

namespace Gedmo\Translatable;

use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableMappingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Gedmo\Translatable\TranslatableListener|mixed
     */
    public $translatableListener;
    const TEST_YAML_ENTITY_CLASS = 'Mapping\Fixture\Yaml\User';
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
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Translatable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
        $this->assertArrayHasKey('translationClass', $config);
        $this->assertEquals('Translatable\Fixture\PersonTranslation', $config['translationClass']);
        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(3, $config['fields']);
        $this->assertEquals('password', $config['fields'][0]);
        $this->assertEquals('username', $config['fields'][1]);
        $this->assertArrayHasKey('locale', $config);
        $this->assertEquals('localeField', $config['locale']);
        $this->assertCount(1, $config['fallback']);
        $this->assertTrue($config['fallback']['company']);
    }
}
