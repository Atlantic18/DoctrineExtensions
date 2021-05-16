<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Tool\BaseTestCaseMongoODM;
use Tree\Fixture\Document\Category;

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
class MaterializedPathODMMongoDBRepositoryTest extends BaseTestCaseMongoODM
{
    private const CATEGORY = Category::class;

    /**
     * @var Document\MongoDB\Repository\MaterializedPathRepository
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockDocumentManager($evm);
        $this->populate();

        $this->repo = $this->dm->getRepository(self::CATEGORY);
    }

    /**
     * @test
     */
    public function getRootNodes()
    {
        /** @var CachingIterator $result */
        $result = $this->repo->getRootNodes('title');

        $this->assertEquals(3, \iterator_count($result));
        $result->rewind();

        $result->rewind();

        $this->assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Food', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Sports', $result->current()->getTitle());
    }

    /**
     * @test
     */
    public function getChildren()
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertEquals(5, \iterator_count($result));
        $result->rewind();

        $result->rewind();
        $this->assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Food', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Vegitables', $result->current()->getTitle());

        // Get all children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', false);

        $this->assertEquals(4, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        $this->assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertEquals(3, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        $this->assertEquals('Food', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', false);

        $this->assertEquals(2, \iterator_count($result));
        $result->rewind();
        $this->assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Vegitables', $result->current()->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        $this->assertEquals(9, \iterator_count($result));
        $result->rewind();
        $this->assertEquals('Best Whisky', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Food', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Sports', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Vegitables', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Whisky', $result->current()->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        $this->assertEquals(3, \iterator_count($result));
        $result->rewind();
        $this->assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Food', $result->current()->getTitle());
        $result->next();
        $this->assertEquals('Sports', $result->current()->getTitle());
    }

    /**
     * @test
     */
    public function getTree()
    {
        $tree = $this->repo->getTree();

        $this->assertEquals(9, \iterator_count($tree));
        $tree->rewind();
        $this->assertEquals('Drinks', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Whisky', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Best Whisky', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Food', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Fruits', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Vegitables', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Carrots', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Potatoes', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Sports', $tree->current()->getTitle());

        // Get a specific tree
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots->current());

        $this->assertEquals(3, \iterator_count($tree));
        $tree->rewind();
        $this->assertEquals('Drinks', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Whisky', $tree->current()->getTitle());
        $tree->next();
        $this->assertEquals('Best Whisky', $tree->current()->getTitle());
    }

    /**
     * @test
     */
    public function childrenHierarchy()
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

        // Tree of one specific root
        $roots = $this->repo->getRootNodes();
        $drinks = $roots->current();
        $roots->next();
        $food = $roots->current();
        $roots->next();
        $tree = $this->repo->childrenHierarchy();

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($drinks, false, [], true);

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($food, true);

        $this->assertEquals(2, count($tree));
        $this->assertEquals('Fruits', $tree[0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($food, true, [], true);

        $this->assertEquals(1, count($tree));
        $this->assertEquals(2, count($tree[0]['__children']));
        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], false);

        $this->assertEquals('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], true);

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
        ];
    }

    public function createCategory()
    {
        $class = self::CATEGORY;

        return new $class();
    }

    private function populate()
    {
        $root = $this->createCategory();
        $root->setTitle('Food');

        $root2 = $this->createCategory();
        $root2->setTitle('Sports');

        $child = $this->createCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = $this->createCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = $this->createCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $drinks = $this->createCategory();
        $drinks->setTitle('Drinks');

        $whisky = $this->createCategory();
        $whisky->setTitle('Whisky');
        $whisky->setParent($drinks);

        $bestWhisky = $this->createCategory();
        $bestWhisky->setTitle('Best Whisky');
        $bestWhisky->setParent($whisky);

        $this->dm->persist($root);
        $this->dm->persist($root2);
        $this->dm->persist($child);
        $this->dm->persist($child2);
        $this->dm->persist($childsChild);
        $this->dm->persist($potatoes);
        $this->dm->persist($drinks);
        $this->dm->persist($whisky);
        $this->dm->persist($bestWhisky);

        $this->dm->flush();
    }
}
