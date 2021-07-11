<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Composer\Autoload\ClassLoader;
use Doctrine\DBAL\Types\Type;

/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christoph Krämer <cevou@gmx.de>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

// Ignore deprecation warnings that occur in PHPUnit 6 on PHP 7.4
error_reporting(E_ALL & ~E_DEPRECATED);

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__.'/../vendor'));

/** @var $loader ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

$loader->add('Gedmo\\Mapping\\Mock', __DIR__);
$loader->add('Tool', __DIR__.'/Gedmo');
// fixture namespaces
$loader->add('Translator\\Fixture', __DIR__.'/Gedmo');
$loader->add('Translatable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Timestampable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Blameable\\Fixture', __DIR__.'/Gedmo');
$loader->add('IpTraceable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Tree\\Fixture', __DIR__.'/Gedmo');
$loader->add('Sluggable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Sortable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Mapping\\Fixture', __DIR__.'/Gedmo');
$loader->add('Loggable\\Fixture', __DIR__.'/Gedmo');
$loader->add('SoftDeleteable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Uploadable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Wrapper\\Fixture', __DIR__.'/Gedmo');
$loader->add('ReferenceIntegrity\\Fixture', __DIR__.'/Gedmo');
$loader->add('References\\Fixture', __DIR__.'/Gedmo');
// stubs
$loader->add('Gedmo\\Uploadable\\Stub', __DIR__);

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
Gedmo\DoctrineExtensions::registerAnnotations();

$reader = new AnnotationReader();
$reader = new CachedReader($reader, new ArrayCache());
$_ENV['annotation_reader'] = $reader;

// Register datetime_immutable for legacy PHP/Doctrine versions.
if (!Type::hasType('datetime_immutable')) {
  Type::addType('datetime_immutable', Type::getType('datetime'));
}

// Fake DateTimeImmutable for PHP 5.4.
if (!class_exists('DateTimeImmutable')) {
  class DateTimeImmutable extends \DateTime
  {
    
  }
}
