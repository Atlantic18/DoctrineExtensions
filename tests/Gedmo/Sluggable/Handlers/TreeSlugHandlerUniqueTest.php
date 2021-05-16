<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Sluggable\Fixture\Handler\TreeSlug;
use Tool\BaseTestCaseORM;

class TreeSlugHandlerUniqueTest extends BaseTestCaseORM
{
    public const TARGET = 'Sluggable\\Fixture\\Handler\\TreeSlug';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testUniqueRoot()
    {
        $foo1 = new TreeSlug();
        $foo1->setTitle('Foo');

        $foo2 = new TreeSlug();
        $foo2->setTitle('Foo');

        $this->em->persist($foo1);
        $this->em->persist($foo2);

        $this->em->flush();

        $this->assertEquals('foo', $foo1->getSlug());
        $this->assertEquals('foo-1', $foo2->getSlug());
    }

    public function testUniqueLeaf()
    {
        $root = new TreeSlug();
        $root->setTitle('root');

        $foo1 = new TreeSlug();
        $foo1->setTitle('Foo');
        $foo1->setParent($root);

        $foo2 = new TreeSlug();
        $foo2->setTitle('Foo');
        $foo2->setParent($root);

        $this->em->persist($root);
        $this->em->persist($foo1);
        $this->em->persist($foo2);

        $this->em->flush();

        $this->assertEquals('root/foo', $foo1->getSlug());
        $this->assertEquals('root/foo-1', $foo2->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
