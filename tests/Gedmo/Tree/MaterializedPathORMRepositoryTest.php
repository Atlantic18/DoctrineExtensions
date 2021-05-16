<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathORMRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = 'Tree\\Fixture\\MPCategory';
    public const CATEGORY_WITH_TRIMMED_SEPARATOR = 'Tree\\Fixture\\MPCategoryWithTrimmedSeparator';

    /** @var \Gedmo\Tree\Entity\Repository\MaterializedPathRepository */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
        $this->populate();

        $this->repo = $this->em->getRepository(self::CATEGORY);
    }

    /**
     * @test
     */
    public function getRootNodes()
    {
        $result = $this->repo->getRootNodes('title');

        $this->assertCount(3, $result);
        $this->assertEquals('Drinks', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getPath()
    {
        $childNode = $this->repo->findOneBy(['title' => 'Carrots']);

        $result = $this->repo->getPath($childNode);

        $this->assertCount(3, $result);
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('Vegitables', $result[1]->getTitle());
        $this->assertEquals('Carrots', $result[2]->getTitle());

        $rootNode = $this->repo->findOneBy(['title' => 'Sports']);

        $result = $this->repo->getPath($rootNode);

        $this->assertCount(1, $result);
        $this->assertEquals('Sports', $result[0]->getTitle());
    }

    /**
     * @test
     */
    public function getChildren()
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        $this->assertCount(4, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Potatoes', $result[2]->getTitle());
        $this->assertEquals('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertCount(5, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Fruits', $result[2]->getTitle());
        $this->assertEquals('Potatoes', $result[3]->getTitle());
        $this->assertEquals('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');

        $this->assertCount(2, $result);
        $this->assertEquals('Fruits', $result[0]->getTitle());
        $this->assertEquals('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertCount(3, $result);
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        $this->assertCount(9, $result);
        $this->assertEquals('Best Whisky', $result[0]->getTitle());
        $this->assertEquals('Carrots', $result[1]->getTitle());
        $this->assertEquals('Drinks', $result[2]->getTitle());
        $this->assertEquals('Food', $result[3]->getTitle());
        $this->assertEquals('Fruits', $result[4]->getTitle());
        $this->assertEquals('Potatoes', $result[5]->getTitle());
        $this->assertEquals('Sports', $result[6]->getTitle());
        $this->assertEquals('Vegitables', $result[7]->getTitle());
        $this->assertEquals('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        $this->assertCount(3, $result);
        $this->assertEquals('Drinks', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getChildrenForEntityWithTrimmedSeparators()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $this->populate(self::CATEGORY_WITH_TRIMMED_SEPARATOR);

        $this->repo = $this->em->getRepository(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        $this->assertCount(4, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Potatoes', $result[2]->getTitle());
        $this->assertEquals('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertCount(5, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Fruits', $result[2]->getTitle());
        $this->assertEquals('Potatoes', $result[3]->getTitle());
        $this->assertEquals('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');
        $this->assertCount(2, $result);
        $this->assertEquals('Fruits', $result[0]->getTitle());
        $this->assertEquals('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertCount(3, $result);
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        $this->assertCount(9, $result);
        $this->assertEquals('Best Whisky', $result[0]->getTitle());
        $this->assertEquals('Carrots', $result[1]->getTitle());
        $this->assertEquals('Drinks', $result[2]->getTitle());
        $this->assertEquals('Food', $result[3]->getTitle());
        $this->assertEquals('Fruits', $result[4]->getTitle());
        $this->assertEquals('Potatoes', $result[5]->getTitle());
        $this->assertEquals('Sports', $result[6]->getTitle());
        $this->assertEquals('Vegitables', $result[7]->getTitle());
        $this->assertEquals('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        $this->assertCount(3, $result);
        $this->assertEquals('Drinks', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getTree()
    {
        $tree = $this->repo->getTree();

        $this->assertCount(9, $tree);
        $this->assertEquals('Drinks', $tree[0]->getTitle());
        $this->assertEquals('Whisky', $tree[1]->getTitle());
        $this->assertEquals('Best Whisky', $tree[2]->getTitle());
        $this->assertEquals('Food', $tree[3]->getTitle());
        $this->assertEquals('Fruits', $tree[4]->getTitle());
        $this->assertEquals('Vegitables', $tree[5]->getTitle());
        $this->assertEquals('Carrots', $tree[6]->getTitle());
        $this->assertEquals('Potatoes', $tree[7]->getTitle());
        $this->assertEquals('Sports', $tree[8]->getTitle());

        // Get tree from a specific root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots[0]);

        $this->assertCount(3, $tree);
        $this->assertEquals('Drinks', $tree[0]->getTitle());
        $this->assertEquals('Whisky', $tree[1]->getTitle());
        $this->assertEquals('Best Whisky', $tree[2]->getTitle());
    }

    public function testChildrenHierarchyMethod()
    {
        $tree = $this->repo->childrenHierarchy();

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
        $vegitablesChildren = $tree[1]['__children'][1]['__children'];
        $this->assertEquals('Food', $tree[1]['title']);
        $this->assertEquals('Fruits', $tree[1]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['__children'][1]['title']);
        $this->assertEquals('Carrots', $vegitablesChildren[0]['title']);
        $this->assertEquals('Potatoes', $vegitablesChildren[1]['title']);
        $this->assertEquals('Sports', $tree[2]['title']);

        // Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0]);

        $this->assertEquals('Whisky', $tree[0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($roots[0], false, [], true);

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[1], true);

        $this->assertEquals(2, count($tree));
        $this->assertEquals('Fruits', $tree[0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($roots[1], true, [], true);

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['__children']));
        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], false);

        $this->assertEquals('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], true);

        $this->assertEquals('<ul><li>Drinks<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul></li></ul>', $tree);
    }

    public function testChildCount()
    {
        // Count all
        $count = $this->repo->childCount();

        $this->assertEquals(9, $count);

        // Count all, but only direct ones
        $count = $this->repo->childCount(null, true);

        $this->assertEquals(3, $count);

        // Count food children
        $food = $this->repo->findOneBy(['title' => 'Food']);
        $count = $this->repo->childCount($food);

        $this->assertEquals(4, $count);

        // Count food children, but only direct ones
        $count = $this->repo->childCount($food, true);

        $this->assertEquals(2, $count);
    }

    public function testChildCountIfAnObjectIsPassedWhichIsNotAnInstanceOfTheEntityClassThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidArgumentException');
        $this->repo->childCount(new \DateTime());
    }

    public function testChildCountIfAnObjectIsPassedIsAnInstanceOfTheEntityClassButIsNotHandledByUnitOfWorkThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidArgumentException');
        $this->repo->childCount($this->createCategory());
    }

    public function testIssue458()
    {
        $this->em->clear();

        $node = $this->repo->findOneBy(['title' => 'Fruits']);
        $newNode = $this->createCategory();
        $parent = $node->getParent();

        $this->assertFalse($parent->__isInitialized());

        $newNode->setTitle('New Node');
        $newNode->setParent($parent);

        $this->em->persist($newNode);
        $this->em->flush();

        $this->assertRegexp('/Food\-\d+,New\sNode\-\d+/', $newNode->getPath());
        $this->assertEquals(2, $newNode->getLevel());
    }

    public function testChangeChildrenIndex()
    {
        $childrenIndex = 'myChildren';
        $this->repo->setChildrenIndex($childrenIndex);

        $tree = $this->repo->childrenHierarchy();

        $this->assertIsArray($tree[0][$childrenIndex]);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::CATEGORY_WITH_TRIMMED_SEPARATOR,
        ];
    }

    public function createCategory($class = null)
    {
        if (!$class) {
            $class = self::CATEGORY;
        }

        return new $class();
    }

    private function populate($class = null)
    {
        $root = $this->createCategory($class);
        $root->setTitle('Food');

        $root2 = $this->createCategory($class);
        $root2->setTitle('Sports');

        $child = $this->createCategory($class);
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = $this->createCategory($class);
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = $this->createCategory($class);
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory($class);
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $drinks = $this->createCategory($class);
        $drinks->setTitle('Drinks');

        $whisky = $this->createCategory($class);
        $whisky->setTitle('Whisky');
        $whisky->setParent($drinks);

        $bestWhisky = $this->createCategory($class);
        $bestWhisky->setTitle('Best Whisky');
        $bestWhisky->setParent($whisky);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->persist($drinks);
        $this->em->persist($whisky);
        $this->em->persist($bestWhisky);

        $this->em->flush();
    }
}
