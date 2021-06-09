<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Query;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootCategory;

/**
 * Tests the tree object hydrator
 *
 * @author Ilija Tovilo <ilija.tovilo@me.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeObjectHydratorTest extends BaseTestCaseORM
{
    public const CATEGORY = 'Tree\\Fixture\\Category';
    public const ROOT_CATEGORY = 'Tree\\Fixture\\RootCategory';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);

        $this->em->getConfiguration()->addCustomHydrationMode('tree', 'Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator');
    }

    public function testFullTreeHydration()
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $result = $repo->createQueryBuilder('node')
            ->orderBy('node.lft', 'ASC')
            ->getQuery()
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        $this->assertEquals(count($result), 1);

        $food = $result[0];
        $this->assertEquals($food->getTitle(), 'Food');
        $this->assertEquals(count($food->getChildren()), 4);

        $fruits = $food->getChildren()->get(0);
        $this->assertEquals($fruits->getTitle(), 'Fruits');
        $this->assertEquals(count($fruits->getChildren()), 2);

        $vegetables = $food->getChildren()->get(1);
        $this->assertEquals($vegetables->getTitle(), 'Vegetables');
        $this->assertEquals(count($vegetables->getChildren()), 0);

        $milk = $food->getChildren()->get(2);
        $this->assertEquals($milk->getTitle(), 'Milk');
        $this->assertEquals(count($milk->getChildren()), 0);

        $meat = $food->getChildren()->get(3);
        $this->assertEquals($meat->getTitle(), 'Meat');
        $this->assertEquals(count($meat->getChildren()), 0);

        $oranges = $fruits->getChildren()->get(0);
        $this->assertEquals($oranges->getTitle(), 'Oranges');
        $this->assertEquals(count($oranges->getChildren()), 0);

        $citrons = $fruits->getChildren()->get(1);
        $this->assertEquals($citrons->getTitle(), 'Citrons');
        $this->assertEquals(count($citrons->getChildren()), 0);

        // Make sure only one query was executed
        $this->assertEquals(count($stack->queries), 1);
    }

    public function testPartialTreeHydration()
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $result = $repo->getChildrenQuery($fruits, false, null, 'ASC', true)
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        $this->assertEquals(count($result), 1);

        $fruits = $result[0];
        $this->assertEquals($fruits->getTitle(), 'Fruits');
        $this->assertEquals(count($fruits->getChildren()), 2);

        $oranges = $fruits->getChildren()->get(0);
        $this->assertEquals($oranges->getTitle(), 'Oranges');
        $this->assertEquals(count($oranges->getChildren()), 0);

        $citrons = $fruits->getChildren()->get(1);
        $this->assertEquals($citrons->getTitle(), 'Citrons');
        $this->assertEquals(count($citrons->getChildren()), 0);

        $this->assertEquals(count($stack->queries), 2);
    }

    public function testMultipleRootNodesTreeHydration()
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $food = $repo->findOneBy(['title' => 'Food']);

        $result = $repo->getChildrenQuery($food)
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        $this->assertEquals(count($result), 4);

        $fruits = $result[0];
        $this->assertEquals($fruits->getTitle(), 'Fruits');
        $this->assertEquals(count($fruits->getChildren()), 2);

        $vegetables = $result[1];
        $this->assertEquals($vegetables->getTitle(), 'Vegetables');
        $this->assertEquals(count($vegetables->getChildren()), 0);

        $milk = $result[2];
        $this->assertEquals($milk->getTitle(), 'Milk');
        $this->assertEquals(count($milk->getChildren()), 0);

        $meat = $result[3];
        $this->assertEquals($meat->getTitle(), 'Meat');
        $this->assertEquals(count($meat->getChildren()), 0);

        $oranges = $fruits->getChildren()->get(0);
        $this->assertEquals($oranges->getTitle(), 'Oranges');
        $this->assertEquals(count($oranges->getChildren()), 0);

        $citrons = $fruits->getChildren()->get(1);
        $this->assertEquals($citrons->getTitle(), 'Citrons');
        $this->assertEquals(count($citrons->getChildren()), 0);

        $this->assertEquals(count($stack->queries), 2);
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $food = new RootCategory();
        $food->setTitle('Food');

        $fruits = new RootCategory();
        $fruits->setTitle('Fruits');

        $vegetables = new RootCategory();
        $vegetables->setTitle('Vegetables');

        $milk = new RootCategory();
        $milk->setTitle('Milk');

        $meat = new RootCategory();
        $meat->setTitle('Meat');

        $oranges = new RootCategory();
        $oranges->setTitle('Oranges');

        $citrons = new RootCategory();
        $citrons->setTitle('Citrons');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsLastChildOf($fruits, $food)
            ->persistAsLastChildOf($vegetables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsLastChildOf($oranges, $fruits)
            ->persistAsLastChildOf($citrons, $fruits);

        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::ROOT_CATEGORY,
        ];
    }
}
