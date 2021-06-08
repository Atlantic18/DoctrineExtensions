<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepositoryTest extends BaseTestCaseORM
{
    const CATEGORY = 'Tree\\Fixture\\Closure\\Category';
    const CLOSURE = 'Tree\\Fixture\\Closure\\CategoryClosure';
    const CATEGORY_WITHOUT_LEVEL = 'Tree\\Fixture\\Closure\\CategoryWithoutLevel';
    const CATEGORY_WITHOUT_LEVEL_CLOSURE = 'Tree\\Fixture\\Closure\\CategoryWithoutLevelClosure';

    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testChildCount()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);

        // Count all
        $count = $repo->childCount();
        $this->assertEquals(15, $count);

        // Count all, but only direct ones
        $count = $repo->childCount(null, true);
        $this->assertEquals(2, $count);

        // Count food children
        $food = $repo->findOneBy(['title' => 'Food']);
        $count = $repo->childCount($food);
        $this->assertEquals(11, $count);

        // Count food children, but only direct ones
        $food = $repo->findOneBy(['title' => 'Food']);
        $count = $repo->childCount($food, true);
        $this->assertEquals(3, $count);
    }

    public function testPath()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $path = $repo->getPath($fruits);
        $this->assertCount(2, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());

        $strawberries = $repo->findOneBy(['title' => 'Strawberries']);
        $path = $repo->getPath($strawberries);
        $this->assertCount(4, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());
        $this->assertEquals('Berries', $path[2]->getTitle());
        $this->assertEquals('Strawberries', $path[3]->getTitle());
    }

    public function testChildren()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        // direct children of node, sorted by title ascending order. NOT including the root node
        $children = $repo->children($fruits, true, 'title');
        $this->assertCount(3, $children);
        $this->assertEquals('Berries', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Oranges', $children[2]->getTitle());

        // direct children of node, sorted by title ascending order. including the root node
        $children = $repo->children($fruits, true, 'title', 'asc', true);
        $this->assertCount(4, $children);
        $this->assertEquals('Berries', $children[0]->getTitle());
        $this->assertEquals('Fruits', $children[1]->getTitle());
        $this->assertEquals('Lemons', $children[2]->getTitle());
        $this->assertEquals('Oranges', $children[3]->getTitle());

        // all children of node, NOT including the root
        $children = $repo->children($fruits);
        $this->assertCount(4, $children);
        $this->assertEquals('Oranges', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Berries', $children[2]->getTitle());
        $this->assertEquals('Strawberries', $children[3]->getTitle());

        // all children of node, including the root
        $children = $repo->children($fruits, false, 'title', 'asc', true);
        $this->assertCount(5, $children);
        $this->assertEquals('Berries', $children[0]->getTitle());
        $this->assertEquals('Fruits', $children[1]->getTitle());
        $this->assertEquals('Lemons', $children[2]->getTitle());
        $this->assertEquals('Oranges', $children[3]->getTitle());
        $this->assertEquals('Strawberries', $children[4]->getTitle());

        // direct root nodes
        $children = $repo->children(null, true, 'title');
        $this->assertCount(2, $children);
        $this->assertEquals('Food', $children[0]->getTitle());
        $this->assertEquals('Sports', $children[1]->getTitle());

        // all tree
        $children = $repo->children();
        $this->assertCount(15, $children);
    }

    public function testSingleNodeRemoval()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $repo->removeFromTree($fruits);
        // ensure in memory node integrity
        $this->em->flush();

        $food = $repo->findOneBy(['title' => 'Food']);
        $children = $repo->children($food, true);
        $this->assertCount(5, $children);

        $berries = $repo->findOneBy(['title' => 'Berries']);
        $this->assertEquals(1, $repo->childCount($berries, true));

        $lemons = $repo->findOneBy(['title' => 'Lemons']);
        $this->assertEquals(0, $repo->childCount($lemons, true));

        $repo->removeFromTree($food);

        $vegitables = $repo->findOneBy(['title' => 'Vegitables']);
        $this->assertEquals(2, $repo->childCount($vegitables, true));
        $this->assertNull($vegitables->getParent());

        $repo->removeFromTree($lemons);
        $this->assertCount(5, $repo->children(null, true));
    }

    public function testBuildTreeWithLevelProperty()
    {
        $this->populate();

        $this->buildTreeTests(self::CATEGORY);
    }

    public function testBuildTreeWithoutLevelProperty()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $this->buildTreeTests(self::CATEGORY_WITHOUT_LEVEL);
    }

    public function testHavingLevelPropertyAvoidsSubqueryInSelectInGetNodesHierarchy()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        $this->assertFalse(strpos($qb->getQuery()->getDql(), '(SELECT MAX('));
    }

    public function testNotHavingLevelPropertyUsesASubqueryInSelectInGetNodesHierarchy()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $repo = $this->em->getRepository(self::CATEGORY_WITHOUT_LEVEL);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITHOUT_LEVEL);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        $this->assertTrue(((bool) strpos($qb->getQuery()->getDql(), '(SELECT MAX(')));
    }

    public function test_changeChildrenIndex()
    {
        $this->populate(self::CATEGORY);

        $childrenIndex = 'myChildren';
        $repo = $this->em->getRepository(self::CATEGORY);
        $repo->setChildrenIndex($childrenIndex);

        $tree = $repo->childrenHierarchy();

        $this->assertIsArray($tree[0][$childrenIndex]);
    }

    // Utility Methods

    protected function buildTreeTests($class)
    {
        $repo = $this->em->getRepository($class);
        $sortOption = ['childSort' => ['field' => 'title', 'dir' => 'asc']];

        $testClosure = function (ClosureTreeRepositoryTest $phpUnit, array $tree, $includeNode = false, $whichTree = 'both', $includeNewNode = false) {
            if ('both' === $whichTree || 'first' === $whichTree) {
                $boringFood = $includeNewNode ? ($includeNode ? $tree[0]['__children'][0] : $tree[0]) : null;
                $fruitsIndex = $includeNewNode ? 1 : 0;
                $milkIndex = $includeNewNode ? 2 : 1;
                $fruits = $includeNode ? $tree[0]['__children'][$fruitsIndex] : $tree[$fruitsIndex];
                $milk = $includeNode ? $tree[0]['__children'][$milkIndex] : $tree[$milkIndex];
                $vegitables = $includeNewNode ? $boringFood['__children'][0] : ($includeNode ? $tree[0]['__children'][2] : $tree[2]);

                if ($includeNode) {
                    $phpUnit->assertEquals('Food', $tree[0]['title']);
                }

                $phpUnit->assertEquals('Fruits', $fruits['title']);
                $phpUnit->assertEquals('Berries', $fruits['__children'][0]['title']);
                $phpUnit->assertEquals('Strawberries', $fruits['__children'][0]['__children'][0]['title']);
                $phpUnit->assertEquals('Milk', $milk['title']);
                $phpUnit->assertEquals('Cheese', $milk['__children'][0]['title']);
                $phpUnit->assertEquals('Mould cheese', $milk['__children'][0]['__children'][0]['title']);

                if ($boringFood) {
                    $phpUnit->assertEquals('Boring Food', $boringFood['title']);
                }

                $phpUnit->assertEquals('Vegitables', $vegitables['title']);
                $phpUnit->assertEquals('Cabbages', $vegitables['__children'][0]['title']);
                $phpUnit->assertEquals('Carrots', $vegitables['__children'][1]['title']);
            }

            if ('both' === $whichTree || 'second' === $whichTree) {
                $root = 'both' === $whichTree ? $tree[1] : $tree[0];
                $soccer = $includeNode ? $root['__children'][0] : $root;

                if ($includeNode) {
                    $phpUnit->assertEquals('Sports', $root['title']);
                }

                $phpUnit->assertEquals('Soccer', $soccer['title']);
                $phpUnit->assertEquals('Indoor Soccer', $soccer['__children'][0]['title']);
            }
        };

        // All trees
        $tree = $repo->childrenHierarchy(null, false, $sortOption);

        $testClosure($this, $tree, true, 'both');

        $roots = $repo->getRootNodes();

        // First root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'first');

        // First root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'first');

        // Second root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'second');

        // Second root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'second');

        $food = $repo->findOneBy(['title' => 'Food']);
        $vegitables = $repo->findOneBy(['title' => 'Vegitables']);

        $boringFood = new $class();
        $boringFood->setTitle('Boring Food');
        $boringFood->setParent($food);
        $vegitables->setParent($boringFood);

        $this->em->persist($boringFood);

        $this->em->flush();

        // First root tree, after inserting a new node in the middle. This includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'first', true);

        // First root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'first', true);

        // Second root tree, after inserting a new node in the middle. This includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'second', true);

        // Second root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'second', false);

        // Test a subtree, including node
        $node = $repo->findOneBy(['title' => 'Fruits']);
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption,
            true
        );

        $this->assertEquals('Fruits', $tree[0]['title']);
        $this->assertEquals('Berries', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Strawberries', $tree[0]['__children'][0]['__children'][0]['title']);

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption
        );

        $this->assertEquals('Berries', $tree[0]['title']);
        $this->assertEquals('Strawberries', $tree[0]['__children'][0]['title']);

        // First Tree Direct Nodes, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption,
            true
        );

        $food = $tree[0];
        $this->assertEquals('Food', $food['title']);
        $this->assertEquals(3, is_array($food['__children']) || $food['__children'] instanceof \Countable ? count($food['__children']) : 0);
        $this->assertEquals('Boring Food', $food['__children'][0]['title']);
        $this->assertEquals('Fruits', $food['__children'][1]['title']);
        $this->assertEquals('Milk', $food['__children'][2]['title']);

        // First Tree Direct Nodes, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption
        );

        $this->assertEquals(3, is_array($tree) || $tree instanceof \Countable ? count($tree) : 0);
        $this->assertEquals('Boring Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $tree[1]['title']);
        $this->assertEquals('Milk', $tree[2]['title']);

        // Helper Closures
        $getTree = function ($includeNode) use ($repo, $roots, $sortOption) {
            return $repo->childrenHierarchy(
                $roots[0],
                true,
                array_merge($sortOption, ['decorate' => true]),
                $includeNode
            );
        };
        $getTreeHtml = function ($includeNode) {
            $baseHtml = '<li>Boring Food<ul><li>Vegitables<ul><li>Cabbages</li><li>Carrots</li></ul></li></ul></li><li>Fruits<ul><li>Berries<ul><li>Strawberries</li></ul></li><li>Lemons</li><li>Oranges</li></ul></li><li>Milk<ul><li>Cheese<ul><li>Mould cheese</li></ul></li></ul></li></ul>';

            return $includeNode ? '<ul><li>Food<ul>'.$baseHtml.'</li></ul>' : '<ul>'.$baseHtml;
        };

        // First Tree - Including Root Node - Html test
        $this->assertEquals($getTreeHtml(true), $getTree(true));

        // First Tree - Not including Root Node - Html test
        $this->assertEquals($getTreeHtml(false), $getTree(false));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::CLOSURE,
            self::CATEGORY_WITHOUT_LEVEL,
            self::CATEGORY_WITHOUT_LEVEL_CLOSURE,
        ];
    }

    private function populate($class = self::CATEGORY)
    {
        $food = new $class();
        $food->setTitle('Food');
        $this->em->persist($food);

        $vegitables = new $class();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->em->persist($vegitables);

        $fruits = new $class();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->em->persist($fruits);

        $oranges = new $class();
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->em->persist($oranges);

        $lemons = new $class();
        $lemons->setTitle('Lemons');
        $lemons->setParent($fruits);
        $this->em->persist($lemons);

        $berries = new $class();
        $berries->setTitle('Berries');
        $berries->setParent($fruits);
        $this->em->persist($berries);

        $strawberries = new $class();
        $strawberries->setTitle('Strawberries');
        $strawberries->setParent($berries);
        $this->em->persist($strawberries);

        $cabbages = new $class();
        $cabbages->setTitle('Cabbages');
        $cabbages->setParent($vegitables);
        $this->em->persist($cabbages);

        $carrots = new $class();
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegitables);
        $this->em->persist($carrots);

        $milk = new $class();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->em->persist($milk);

        $cheese = new $class();
        $cheese->setTitle('Cheese');
        $cheese->setParent($milk);
        $this->em->persist($cheese);

        $mouldCheese = new $class();
        $mouldCheese->setTitle('Mould cheese');
        $mouldCheese->setParent($cheese);
        $this->em->persist($mouldCheese);

        $sports = new $class();
        $sports->setTitle('Sports');
        $this->em->persist($sports);

        $soccer = new $class();
        $soccer->setTitle('Soccer');
        $soccer->setParent($sports);
        $this->em->persist($soccer);

        $indoorSoccer = new $class();
        $indoorSoccer->setTitle('Indoor Soccer');
        $indoorSoccer->setParent($soccer);
        $this->em->persist($indoorSoccer);

        $this->em->flush();
        $this->em->clear();
    }
}
