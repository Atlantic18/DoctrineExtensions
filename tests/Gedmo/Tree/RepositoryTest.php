<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Category;
use Tree\Fixture\CategoryUuid;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = 'Tree\\Fixture\\Category';
    public const CATEGORY_UUID = 'Tree\\Fixture\\CategoryUuid';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testBasicFunctions()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $food = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Food']);

        // test childCount

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($food);
        $this->assertEquals(4, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($food, true);
        $this->assertEquals(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount();
        $this->assertEquals(6, $childCount);

        // test children

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($vegies);

        $this->assertCount(2, $children);
        $this->assertEquals('Carrots', $children[0]->getTitle());
        $this->assertEquals('Potatoes', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food);

        $this->assertCount(4, $children);
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());
        $this->assertEquals('Carrots', $children[2]->getTitle());
        $this->assertEquals('Potatoes', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food, true);

        $this->assertCount(2, $children);
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children();

        $this->assertCount(6, $children);

        // path

        $path = $this->em->getRepository(self::CATEGORY)
            ->getPath($vegies);

        $this->assertCount(2, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());

        $carrots = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Carrots']);

        $path = $this->em->getRepository(self::CATEGORY)
            ->getPath($carrots);

        $this->assertCount(3, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());

        // leafs

        $leafs = $this->em->getRepository(self::CATEGORY)
            ->getLeafs();

        $this->assertCount(4, $leafs);
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Carrots', $leafs[1]->getTitle());
        $this->assertEquals('Potatoes', $leafs[2]->getTitle());
        $this->assertEquals('Sports', $leafs[3]->getTitle());
    }

    public function testAdvancedFunctions()
    {
        $this->populateMore();
        $onions = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Onions']);
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        $this->assertEquals(11, $left);
        $this->assertEquals(12, $right);

        // move up onions by one position

        $repo->moveUp($onions, 1);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        $this->assertEquals(9, $left);
        $this->assertEquals(10, $right);

        // move down onions by one position
        $repo->moveDown($onions, 1);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        $this->assertEquals(11, $left);
        $this->assertEquals(12, $right);

        // move to the up onions on this level

        $repo->moveUp($onions, true);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        $this->assertEquals(5, $left);
        $this->assertEquals(6, $right);

        // test tree reordering
        // reorder tree by title

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->reorder($food, 'title');

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Cabbages']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(5, $left);
        $this->assertEquals(6, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Carrots']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(7, $left);
        $this->assertEquals(8, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Onions']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(9, $left);
        $this->assertEquals(10, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Potatoes']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(11, $left);
        $this->assertEquals(12, $right);

        // test removal with reparenting

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $repo->removeFromTree($vegies);

        $this->em->clear(); // clear all cached nodes

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $this->assertNull($vegies);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Fruits']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(2, $left);
        $this->assertEquals(3, $right);
        $this->assertEquals('Food', $node->getParent()->getTitle());

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Cabbages']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(4, $left);
        $this->assertEquals(5, $right);
        $this->assertEquals('Food', $node->getParent()->getTitle());
    }

    public function testRootRemoval()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->populateMore();

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->removeFromTree($food);
        $this->em->clear();

        $food = $repo->findOneBy(['title' => 'Food']);
        $this->assertNull($food);

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(1, $left);
        $this->assertEquals(2, $right);
        $this->assertNull($node->getParent());

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        $this->assertEquals(3, $left);
        $this->assertEquals(12, $right);
        $this->assertNull($node->getParent());
    }

    public function testVerificationAndRecover()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->populateMore();
        // test verification of tree

        $this->assertTrue($repo->verify());

        // now lets brake something

        $dql = 'UPDATE '.self::CATEGORY.' node';
        $dql .= ' SET node.lft = 1';
        $dql .= ' WHERE node.id = 8';
        $q = $this->em->createQuery($dql);
        $q->getSingleScalarResult();

        $this->em->clear(); // must clear cached entities

        // verify again

        $result = $repo->verify();
        $this->assertTrue(is_array($result));

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);

        $duplicate = $result[0];
        $missing = $result[1];
        $invalidLeft = $result[2];

        $this->assertEquals('index [1], duplicate', $duplicate);
        $this->assertEquals('index [11], missing', $missing);
        $this->assertEquals('node [8] left is less than parent`s [4] left value', $invalidLeft);

        // test recover functionality
        $repo->recover();
        $this->em->flush();

        $this->assertTrue($repo->verify());
    }

    public function testMoveRootNode()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);

        $repo->moveDown($food, 1);

        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $left = $meta->getReflectionProperty('lft')->getValue($food);
        $right = $meta->getReflectionProperty('rgt')->getValue($food);

        $this->assertEquals(3, $left);
        $this->assertEquals(12, $right);
        $this->assertNull($food->getParent());

        $this->assertTrue($repo->verify());
    }

    public function testIssue273()
    {
        $this->populateUuid();

        $vegies = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Vegitables']);

        $food = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Food']);

        // test childCount

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($food);
        $this->assertEquals(4, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($food, true);
        $this->assertEquals(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount();
        $this->assertEquals(6, $childCount);

        // test children

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($vegies);

        $this->assertCount(2, $children);
        $this->assertEquals('Carrots', $children[0]->getTitle());
        $this->assertEquals('Potatoes', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($food);

        $this->assertCount(4, $children);
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());
        $this->assertEquals('Carrots', $children[2]->getTitle());
        $this->assertEquals('Potatoes', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($food, true);

        $this->assertCount(2, $children);
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children();

        $this->assertCount(6, $children);

        // path

        $path = $this->em->getRepository(self::CATEGORY_UUID)
            ->getPath($vegies);

        $this->assertCount(2, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());

        $carrots = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Carrots']);

        $path = $this->em->getRepository(self::CATEGORY_UUID)
            ->getPath($carrots);

        $this->assertCount(3, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());

        // leafs

        $leafs = $this->em->getRepository(self::CATEGORY_UUID)
            ->getLeafs($path[0]);

        $this->assertCount(3, $leafs);
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Carrots', $leafs[1]->getTitle());
        $this->assertEquals('Potatoes', $leafs[2]->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::CATEGORY_UUID,
        ];
    }

    private function populateMore()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $cabbages = new Category();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');

        $onions = new Category();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');

        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
        $this->em->clear();
    }

    private function populate()
    {
        $root = new Category();
        $root->setTitle('Food');

        $root2 = new Category();
        $root2->setTitle('Sports');

        $child = new Category();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new Category();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new Category();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new Category();
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

    private function populateUuid()
    {
        $root = new CategoryUuid();
        $root->setTitle('Food');

        $root2 = new CategoryUuid();
        $root2->setTitle('Sports');

        $child = new CategoryUuid();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new CategoryUuid();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new CategoryUuid();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new CategoryUuid();
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
