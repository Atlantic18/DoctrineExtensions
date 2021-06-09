<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = 'Tree\\Fixture\\RootCategory';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * Based on issue #342
     *
     * @test
     */
    public function shouldBeAbleToShiftRootNode()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $food = $repo->findOneBy(['title' => 'Food']);
        $acme = new RootCategory();
        $acme->setTitle('Acme');

        $food->setParent($acme);

        $this->em->persist($acme);
        $this->em->persist($food);
        $this->em->flush();

        $this->assertNull($acme->getParent());
        $this->assertSame($acme, $food->getParent());
        $this->assertSame($acme->getId(), $acme->getRoot());
        $this->assertSame($acme->getId(), $food->getRoot());
        $this->assertSame(1, $acme->getLeft());
        $this->assertSame(12, $acme->getRight());
        $this->assertSame(2, $food->getLeft());
        $this->assertSame(11, $food->getRight());
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyAsArray()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $result = $repo->childrenHierarchy();
        $this->assertCount(2, $result);
        $this->assertTrue(isset($result[0]['__children'][0]['__children']));

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $result = $repo->childrenHierarchy($vegies);
        $this->assertCount(2, $result);
        $this->assertCount(0, $result[0]['__children']);

        // Complete Tree
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy();

        $this->assertEquals(2, count($tree));     // Count roots
        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Sports', $tree[1]['title']);
        $this->assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
        $this->assertEquals('Carrots', $tree[0]['__children'][1]['__children'][0]['title']);
        $this->assertEquals('Potatoes', $tree[0]['__children'][1]['__children'][1]['title']);

        // Tree of one specific root, without the root node
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy($roots[0]);

        $this->assertEquals(2, count($tree));     // Count roots
        $this->assertEquals('Fruits', $tree[0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['title']);
        $this->assertEquals('Carrots', $tree[1]['__children'][0]['title']);
        $this->assertEquals('Potatoes', $tree[1]['__children'][1]['title']);

        // Tree of one specific root, with the root node
        $tree = $repo->childrenHierarchy($roots[0], false, [], true);

        $this->assertEquals(1, count($tree));     // Count roots
        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
        $this->assertEquals('Carrots', $tree[0]['__children'][1]['__children'][0]['title']);
        $this->assertEquals('Potatoes', $tree[0]['__children'][1]['__children'][1]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy($roots[0], true);

        $this->assertEquals(2, count($tree));
        $this->assertEquals('Fruits', $tree[0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $repo->childrenHierarchy($roots[0], true, [], true);

        $this->assertEquals(1, count($tree));
        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyAsHtml()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);
        $decorate = true;
        $defaultHtmlTree = $repo->childrenHierarchy($food, false, compact('decorate'));

        $this->assertEquals(
            '<ul><li>Fruits</li><li>Vegitables<ul><li>Carrots</li><li>Potatoes</li></ul></li></ul>',
            $defaultHtmlTree
        );

        // custom title
        $nodeDecorator = function ($node) {
            return '<span>'.$node['title'].'</span>';
        };

        $decoratedHtmlTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'nodeDecorator')
        );

        $this->assertEquals(
            '<ul><li><span>Fruits</span></li><li><span>Vegitables</span><ul><li><span>Carrots</span></li><li><span>Potatoes</span></li></ul></li></ul>',
            $decoratedHtmlTree
        );
        // cli friendly output
        $rootOpen = '';
        $rootClose = '';
        $childOpen = '';
        $childClose = '';
        $nodeDecorator = function ($node) {
            return str_repeat('-', $node['level']).$node['title']."\n";
        };

        $decoratedCliTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'nodeDecorator', 'rootOpen', 'rootClose', 'childOpen', 'childClose')
        );
        $this->assertEquals(
            "-Fruits\n-Vegitables\n--Carrots\n--Potatoes\n",
            $decoratedCliTree
        );

        $rootOpen = function () {return '<ul class="group">'; };
        // check support of the closures in rootClose
        $rootClose = function () {return '</ul><!--rootCloseClosure-->'; };
        $childOpen = function (&$node) {
            return '<li class="depth'.$node['level'].'">';
        };
        // check support of the closures in childClose
        $childClose = function (&$node) {
            return '</li><!--childCloseClosure-->';
        };
        $decoratedHtmlTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'rootOpen', 'rootClose', 'childOpen', 'childClose')
        );

        $this->assertEquals(
            '<ul class="group"><li class="depth1">Fruits</li><!--childCloseClosure--><li class="depth1">Vegitables<ul class="group"><li class="depth2">Carrots</li><!--childCloseClosure--><li class="depth2">Potatoes</li><!--childCloseClosure--></ul><!--rootCloseClosure--></li><!--childCloseClosure--></ul><!--rootCloseClosure-->',
            $decoratedHtmlTree
        );
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyByBuildTreeFunction()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $q = $this->em
            ->createQueryBuilder()
            ->select('node')
            ->from(self::CATEGORY, 'node')
            ->orderBy('node.root, node.lft', 'ASC')
            ->where('node.root = 1')
            ->getQuery()
        ;
        $tree = $repo->buildTree($q->getArrayResult());
        $this->assertCount(1, $tree);
        $this->assertCount(2, $tree[0]['__children']);
        $nodes = [];
        $options = ['decorate' => true];
        $this->assertEquals('', $repo->buildTree($nodes, $options), 'should give empty string when there are no nodes given');
    }

    /**
     * @test
     */
    public function shouldRemoveRootNodeFromTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $this->populateMore();

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->removeFromTree($food);
        $this->em->clear();

        $food = $repo->findOneBy(['title' => 'Food']);
        $this->assertNull($food);

        $node = $repo->findOneBy(['title' => 'Fruits']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
        $this->assertEquals(3, $node->getRoot());
        $this->assertNull($node->getParent());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(10, $node->getRight());
        $this->assertEquals(4, $node->getRoot());
        $this->assertNull($node->getParent());
    }

    /**
     * @test
     */
    public function shouldHandleBasicRepositoryMethods()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneBy(['title' => 'Carrots']);

        $path = $repo->getPath($carrots);
        $this->assertCount(3, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $childCount = $repo->childCount($vegies);
        $this->assertEquals(2, $childCount);

        $food = $repo->findOneBy(['title' => 'Food']);
        $childCount = $repo->childCount($food, true);
        $this->assertEquals(2, $childCount);

        $childCount = $repo->childCount($food);
        $this->assertEquals(4, $childCount);

        $childCount = $repo->childCount();
        $this->assertEquals(6, $childCount);

        $childCount = $repo->childCount(null, true);
        $this->assertEquals(2, $childCount);
    }

    /**
     * @test
     */
    public function shouldHandleAdvancedRepositoryFunctions()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);

        // verification

        $this->assertTrue($repo->verify());

        $dql = 'UPDATE '.self::CATEGORY.' node';
        $dql .= ' SET node.lft = 5';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();

        $this->em->clear(); // must clear cached entities
        $errors = $repo->verify();
        $this->assertCount(2, $errors);
        $this->assertEquals('index [4], missing on tree root: 1', $errors[0]);
        $this->assertEquals('index [5], duplicate on tree root: 1', $errors[1]);

        // test recover functionality
        $repo->recover();
        $this->em->flush();

        $this->assertTrue($repo->verify());

        $this->em->clear();
        $onions = $repo->findOneBy(['title' => 'Onions']);

        $this->assertEquals(11, $onions->getLeft());
        $this->assertEquals(12, $onions->getRight());

        // move up

        $repo->moveUp($onions);

        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());

        $repo->moveUp($onions, true);

        $this->assertEquals(5, $onions->getLeft());
        $this->assertEquals(6, $onions->getRight());

        // move down

        $repo->moveDown($onions, 2);

        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());

        // reorder

        $node = $repo->findOneBy(['title' => 'Food']);
        $repo->reorder($node, 'title', 'ASC', false);

        $node = $repo->findOneBy(['title' => 'Cabbages']);

        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(8, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Onions']);

        $this->assertEquals(9, $node->getLeft());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        $this->assertEquals(11, $node->getLeft());
        $this->assertEquals(12, $node->getRight());

        // leafs

        $leafs = $repo->getLeafs($node);
        $this->assertCount(5, $leafs);
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Cabbages', $leafs[1]->getTitle());
        $this->assertEquals('Carrots', $leafs[2]->getTitle());
        $this->assertEquals('Onions', $leafs[3]->getTitle());
        $this->assertEquals('Potatoes', $leafs[4]->getTitle());

        // remove

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $id = $node->getId();
        $repo->removeFromTree($node);

        $this->assertNull($repo->find($id));

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $id = $node->getId();
        $repo->removeFromTree($node);

        $this->assertNull($repo->find($id));
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Cabbages']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(1, $node->getParent()->getId());
    }

    /**
     * @test
     */
    public function shouldRemoveTreeLeafFromTree()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);
        $onions = $repo->findOneBy(['title' => 'Onions']);
        $id = $onions->getId();
        $repo->removeFromTree($onions);

        $this->assertNull($repo->find($id));
        $this->em->clear();

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $this->assertTrue($repo->verify());
    }

    /**
     * @test
     */
    public function getRootNodesTest()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // Test getRootNodes without custom ordering
        $roots = $repo->getRootNodes();

        $this->assertEquals(2, count($roots));
        $this->assertEquals('Food', $roots[0]->getTitle());
        $this->assertEquals('Sports', $roots[1]->getTitle());

        // Test getRootNodes with custom ordering
        $roots = $repo->getRootNodes('title', 'desc');

        $this->assertEquals(2, count($roots));
        $this->assertEquals('Sports', $roots[0]->getTitle());
        $this->assertEquals('Food', $roots[1]->getTitle());
    }

    /**
     * @test
     */
    public function changeChildrenIndexTest()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $childrenIndex = 'myChildren';
        $repo->setChildrenIndex($childrenIndex);

        $tree = $repo->childrenHierarchy();

        $this->assertIsArray($tree[0][$childrenIndex]);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populateMore()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $cabbages = new RootCategory();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');

        $onions = new RootCategory();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');

        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
    }

    private function populate()
    {
        $root = new RootCategory();
        $root->setTitle('Food');

        $root2 = new RootCategory();
        $root2->setTitle('Sports');

        $child = new RootCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new RootCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new RootCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new RootCategory();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
    }
}
