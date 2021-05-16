<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\Mock\Extension\Encoder\EncoderListener;
use Mapping\Fixture\User;
use Tool\BaseTestCaseORM;

class ExtensionORMTest extends BaseTestCaseORM
{
    public const USER = 'Mapping\\Fixture\\User';

    private $encoderListener;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/Mock/Extension/Encoder/Mapping/Annotations.php';
        $evm = new EventManager();
        $this->encoderListener = new EncoderListener();
        $evm->addEventSubscriber($this->encoderListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testExtensionMetadata()
    {
        $meta = $this->em->getClassMetadata(self::USER);
        $config = $this->encoderListener->getConfiguration($this->em, self::USER);
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
        $this->em->persist($user);
        $this->em->flush();

        $this->assertEquals('c12fead75b49a41d43804e8229cb049d3b91bf42', $user->getName());
        $this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $user->getPassword());
    }

    public function testEventAdapterUsed()
    {
        $mappedSubscriberClass = new \ReflectionClass('Gedmo\\Mapping\\MappedEventSubscriber');
        $getEventAdapterMethod = $mappedSubscriberClass->getMethod('getEventAdapter');
        $getEventAdapterMethod->setAccessible(true);

        $loadClassMetadataEventArgs = new LoadClassMetadataEventArgs(
            $this->em->getClassMetadata(self::USER),
            $this->em
        );
        $eventAdapter = $getEventAdapterMethod->invoke(
            $this->encoderListener,
            $loadClassMetadataEventArgs
        );
        $this->assertEquals('Gedmo\\Mapping\\Mock\\Extension\\Encoder\\Mapping\\Event\\Adapter\\ORM', get_class($eventAdapter));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
        ];
    }
}
