<?php
/**
 * This entity manager configuration works with doctrine 2.1.x and 2.2.x
 * versions. Regarding AnnotationDriver setup it most probably will be changed into
 * xml. Because annotation driver fails to read other classes in same namespace
 */
// connection args, modify at will
$connection = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'password' => null,
    'dbname' => 'test',
    'driver' => 'pdo_mysql',
];
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    exit('cannot find vendors, read README.md how to use composer');
}
// First of all autoloading of vendors
$loader = require __DIR__.'/../vendor/autoload.php';

// gedmo extensions
$loader->add('Gedmo', __DIR__.'/../lib');

// autoloader for Entity namespace
$loader->add('Entity', __DIR__.'/app');

// ensure standard doctrine annotations are registered
Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    __DIR__.'/../vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

// Second configure ORM
// globally used cache driver, in production use APC or memcached
$cache = new Doctrine\Common\Cache\ArrayCache();
// standard annotation reader
$annotationReader = new Doctrine\Common\Annotations\AnnotationReader();
$cachedAnnotationReader = new Doctrine\Common\Annotations\CachedReader(
    $annotationReader, // use reader
    $cache // and a cache driver
);
// create a driver chain for metadata reading
$driverChain = new Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain();
// load superclass metadata mapping only, into driver chain
// also registers Gedmo annotations.NOTE: you can personalize it
Gedmo\DoctrineExtensions::registerAbstractMappingIntoDriverChainORM(
    $driverChain, // our metadata driver chain, to hook into
    $cachedAnnotationReader // our cached annotation reader
);

// now we want to register our application entities,
// for that we need another metadata driver used for Entity namespace
$annotationDriver = new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    $cachedAnnotationReader, // our cached annotation reader
    [__DIR__.'/app/Entity'] // paths to look in
);
// NOTE: driver for application Entity can be different, Yaml, Xml or whatever
// register annotation driver for our application Entity fully qualified namespace
$driverChain->addDriver($annotationDriver, 'Entity');

// general ORM configuration
$config = new Doctrine\ORM\Configuration();
$config->setProxyDir(sys_get_temp_dir());
$config->setProxyNamespace('Proxy');
$config->setAutoGenerateProxyClasses(false); // this can be based on production config.
// register metadata driver
$config->setMetadataDriverImpl($driverChain);
// use our allready initialized cache driver
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Third, create event manager and hook prefered extension listeners
$evm = new Doctrine\Common\EventManager();
// gedmo extension listeners

// sluggable
$sluggableListener = new Gedmo\Sluggable\SluggableListener();
// you should set the used annotation reader to listener, to avoid creating new one for mapping drivers
$sluggableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($sluggableListener);

// tree
$treeListener = new Gedmo\Tree\TreeListener();
$treeListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($treeListener);

// loggable, not used in example
//$loggableListener = new Gedmo\Loggable\LoggableListener;
//$loggableListener->setAnnotationReader($cachedAnnotationReader);
//$loggableListener->setUsername('admin');
//$evm->addEventSubscriber($loggableListener);

// timestampable
$timestampableListener = new Gedmo\Timestampable\TimestampableListener();
$timestampableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($timestampableListener);

// blameable

$blameableListener = new \Gedmo\Blameable\BlameableListener();
$blameableListener->setAnnotationReader($cachedAnnotationReader);
$blameableListener->setUserValue('MyUsername'); // determine from your environment
$evm->addEventSubscriber($blameableListener);

// translatable
$translatableListener = new Gedmo\Translatable\TranslatableListener();
// current translation locale should be set from session or hook later into the listener
// most important, before entity manager is flushed
$translatableListener->setTranslatableLocale('en');
$translatableListener->setDefaultLocale('en');
$translatableListener->setAnnotationReader($cachedAnnotationReader);
$evm->addEventSubscriber($translatableListener);

// sortable, not used in example
//$sortableListener = new Gedmo\Sortable\SortableListener;
//$sortableListener->setAnnotationReader($cachedAnnotationReader);
//$evm->addEventSubscriber($sortableListener);

// mysql set names UTF-8 if required
$evm->addEventSubscriber(new Doctrine\DBAL\Event\Listeners\MysqlSessionInit());
// Finally, create entity manager
return Doctrine\ORM\EntityManager::create($connection, $config, $evm);
