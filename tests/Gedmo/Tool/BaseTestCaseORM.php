<?php

namespace Tool;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseORM extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var QueryAnalyzer
     */
    protected $queryAnalyzer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager(EventManager $evm = null, Configuration $config = null)
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = $config ?? $this->getMockAnnotatedConfig();
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and custom
     * connection
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockCustomEntityManager(array $conn, EventManager $evm = null)
    {
        $config = $this->getMockAnnotatedConfig();
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object with
     * annotation mapping driver
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockMappedEntityManager(EventManager $evm = null)
    {
        $driver = $this->getMockBuilder('Doctrine\DBAL\Driver')->getMock();
        $driver->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getMockBuilder('Doctrine\DBAL\Platforms\MySqlPlatform')->getMock()));

        $conn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->setConstructorArgs([], $driver)
            ->getMock();

        $conn->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($evm ?: $this->getEventManager()));

        $config = $this->getMockAnnotatedConfig();
        $this->em = EntityManager::create($conn, $config);

        return $this->em;
    }

    /**
     * Starts query statistic log
     *
     * @throws \RuntimeException
     */
    protected function startQueryLog()
    {
        if (!$this->em || !$this->em->getConnection()->getDatabasePlatform()) {
            throw new \RuntimeException('EntityManager and database platform must be initialized');
        }
        $this->queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $this->em->getConfiguration()->setSQLLogger($this->queryAnalyzer);
    }

    /**
     * Stops query statistic log and outputs
     * the data to screen or file
     *
     * @param bool $dumpOnlySql
     * @param bool $writeToLog
     *
     * @throws \RuntimeException
     */
    protected function stopQueryLog($dumpOnlySql = false, $writeToLog = false)
    {
        if ($this->queryAnalyzer) {
            $output = $this->queryAnalyzer->getOutput($dumpOnlySql);
            if ($writeToLog) {
                $fileName = __DIR__.'/../../temp/query_debug_'.time().'.log';
                if (false !== ($file = fopen($fileName, 'w+'))) {
                    fwrite($file, $output);
                    fclose($file);
                } else {
                    throw new \RuntimeException('Unable to write to the log file');
                }
            } else {
                echo $output;
            }
        }
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array
     */
    abstract protected function getUsedEntityFixtures();

    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new LoggableListener());
        $evm->addEventSubscriber(new TranslatableListener());
        $evm->addEventSubscriber(new TimestampableListener());
        $evm->addEventSubscriber(new SoftDeleteableListener());

        return $evm;
    }

    /**
     * Get annotation mapping configuration
     *
     * @return \Doctrine\ORM\Configuration
     */
    protected function getMockAnnotatedConfig()
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__.'/../../temp');
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        return $config;
    }
}
