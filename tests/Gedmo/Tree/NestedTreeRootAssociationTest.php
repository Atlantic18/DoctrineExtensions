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
    public const CATEGORY = 'Tree\\Fixture\\RootAssociationCategory';

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

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populate()
    {
        $root = new RootAssociationCategory();
        $root->setTitle('Food');

        $root2 = new RootAssociationCategory();
        $root2->setTitle('Sports');

        $child = new RootAssociationCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new RootAssociationCategory();
        $child2->setTitle('Vegetables');
        $child2->setParent($root);

        $childsChild = new RootAssociationCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

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
