<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootAssociationCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootAssociationTest extends BaseTestCaseORM
{
    const CATEGORY = 'Tree\\Fixture\\RootAssociationCategory';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testRootEntity()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // Foods
        $food = $repo->findOneBy(['title' => 'Food']);
        $this->assertEquals($food->getId(), $food->getRoot()->getId());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $this->assertEquals($food->getId(), $fruits->getRoot()->getId());

        $vegetables = $repo->findOneBy(['title' => 'Vegetables']);
        $this->assertEquals($food->getId(), $vegetables->getRoot()->getId());

        $carrots = $repo->findOneBy(['title' => 'Carrots']);
        $this->assertEquals($food->getId(), $carrots->getRoot()->getId());

        $potatoes = $repo->findOneBy(['title' => 'Potatoes']);
        $this->assertEquals($food->getId(), $potatoes->getRoot()->getId());

        // Sports
        $sports = $repo->findOneBy(['title' => 'Sports']);
        $this->assertEquals($sports->getId(), $sports->getRoot()->getId());
    }

    public function testPositions(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // Foods
        /** @var \Tree\Fixture\RootAssociationCategory $food */
        $food = $repo->findOneBy(['title' => 'Food']);
        $this->assertEquals(1, $food->getLeft());
        $this->assertEquals(10, $food->getRight());

        /** @var \Tree\Fixture\RootAssociationCategory $fruits */
        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $this->assertEquals(2, $fruits->getLeft());
        $this->assertEquals(3, $fruits->getRight());

        /** @var \Tree\Fixture\RootAssociationCategory $vegetables */
        $vegetables = $repo->findOneBy(['title' => 'Vegetables']);
        $this->assertEquals(4, $vegetables->getLeft());
        $this->assertEquals(9, $vegetables->getRight());

        /** @var \Tree\Fixture\RootAssociationCategory $carrots */
        $carrots = $repo->findOneBy(['title' => 'Carrots']);
        $this->assertEquals(5, $carrots->getLeft());
        $this->assertEquals(6, $carrots->getRight());

        /** @var \Tree\Fixture\RootAssociationCategory $potatoes */
        $potatoes = $repo->findOneBy(['title' => 'Potatoes']);
        $this->assertEquals(7, $potatoes->getLeft());
        $this->assertEquals(8, $potatoes->getRight());

        // Sports
        /** @var \Tree\Fixture\RootAssociationCategory $sports */
        $sports = $repo->findOneBy(['title' => 'Sports']);
        $this->assertEquals(1, $sports->getLeft(), 'Another root, so should started from begin');
        $this->assertEquals(2, $sports->getRight());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populate()
    {
        // left 1 right 10
        $root = new RootAssociationCategory();
        $root->setTitle('Food');

        // left 1 right 2
        $root2 = new RootAssociationCategory();
        $root2->setTitle('Sports');

        // left 2 right 3
        $child = new RootAssociationCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        // left 4 right 9
        $child2 = new RootAssociationCategory();
        $child2->setTitle('Vegetables');
        $child2->setParent($root);

        // left 5 right 6
        $childsChild = new RootAssociationCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        // left 7 right 8
        $potatoes = new RootAssociationCategory();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }
}
