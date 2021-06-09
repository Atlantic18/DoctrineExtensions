<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\Mock\Extension\Encoder\EncoderListener;
use Mapping\Fixture\Document\User;
use Tool\BaseTestCaseMongoODM;

class ExtensionODMTest extends BaseTestCaseMongoODM
{
    public const USER = 'Mapping\\Fixture\\Document\\User';

    private $encoderListener;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/Mock/Extension/Encoder/Mapping/Annotations.php';
        $evm = new EventManager();
        $this->encoderListener = new EncoderListener();
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockDocumentManager($evm);
    }

    public function testExtensionMetadata()
    {
        $meta = $this->dm->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->dm, self::USER);
        $this->assertArrayHasKey('encode', $config);
        $this->assertCount(2, $config['encode']);

        $this->assertArrayHasKey('name', $config['encode']);
        $options = $config['encode']['name'];
        $this->assertEquals('sha1', $options['type']);
        $this->assertEquals('xxx', $options['secret']);

        $this->assertArrayHasKey('password', $config['encode']);
        $options = $config['encode']['password'];
        $this->assertEquals('md5', $options['type']);
        $this->assertEmpty($options['secret']);
    }

    public function testGeneratedValues()
    {
        $user = new User();
        $user->setName('encode me');
        $user->setPassword('secret');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertEquals('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        $this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed()
    {
        $mappedSubscriberClass = new \ReflectionClass('Gedmo\\Mapping\\MappedEventSubscriber');
        $getEventAdapterMethod = $mappedSubscriberClass->getMethod('getEventAdapter');
        $getEventAdapterMethod->setAccessible(true);

        $loadClassMetadataEventArgs = new LoadClassMetadataEventArgs(
            $this->dm->getClassMetadata(self::USER),
            $this->dm
        );
        $eventAdapter = $getEventAdapterMethod->invoke(
            $this->encoderListener,
            $loadClassMetadataEventArgs
        );
        $this->assertEquals('Gedmo\\Mapping\\Mock\\Extension\\Encoder\\Mapping\\Event\\Adapter\\ODM', get_class($eventAdapter));
    }
}
