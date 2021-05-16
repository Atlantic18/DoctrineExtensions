<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Tool\BaseTestCaseORM;
use Tree\Fixture\ForeignRootCategory;
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
class NestedTreeRootTest extends BaseTestCaseORM
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
     * @test
     */
    public function shouldRemoveAndSynchronize()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $this->em->remove($vegies);
        $this->em->flush();

        $food = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $food->getLeft());
        $this->assertEquals(4, $food->getRight());

        $vegies = new RootCategory();
        $vegies->setTitle('Vegies');
        $repo->persistAsFirstChildOf($vegies, $food);

        $this->em->flush();
        $this->assertEquals(1, $food->getLeft());
        $this->assertEquals(6, $food->getRight());

        $this->assertEquals(2, $vegies->getLeft());
        $this->assertEquals(3, $vegies->getRight());
    }

    /*public function testHeavyLoad()
    {
        $start = microtime(true);
        $dumpTime = function($start, $msg) {
            $took = microtime(true) - $start;
            $minutes = intval($took / 60); $seconds = $took % 60;
            echo sprintf("%s --> %02d:%02d", $msg, $minutes, $seconds) . PHP_EOL;
        };
        $repo = $this->em->getRepository(self::CATEGORY);
        $parent = null;
        $num = 800;
        for($i = 0; $i < 500; $i++) {
            $cat = new RootCategory;
            $cat->setParent($parent);
            $cat->setTitle('cat'.$i);
            $this->em->persist($cat);
            // siblings
            $rnd = rand(0, 3);
            for ($j = 0; $j < $rnd; $j++) {
                $siblingCat = new RootCategory;
                $siblingCat->setTitle('cat'.$i.$j);
                $siblingCat->setParent($cat);
                $this->em->persist($siblingCat);
            }
            $num += $rnd;
            $parent = $cat;
        }
        $this->em->flush();
        $dumpTime($start, $num.' - inserts took:');
        $start = microtime(true);
        // test moving
        $target = $repo->findOneBy(['title' => 'cat300']);
        $dest = $repo->findOneBy(['title' => 'cat2000']);
        $target->setParent($dest);

        $target2 = $repo->findOneBy(['title' => 'cat450']);
        $dest2 = $repo->findOneBy(['title' => 'cat2500']);
        $target2->setParent($dest2);

        $this->em->flush();
        $dumpTime($start, 'moving took:');
    }*/

    public function testTheTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        $this->assertEquals(2, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(2, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Fruits']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(4, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(9, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(8, $node->getRight());
    }

    public function testSetParentToNull()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $node->setParent(null);

        $this->em->persist($node);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(6, $node->getRight());
        $this->assertEquals(0, $node->getLevel());
    }

    public function testTreeUpdateShiftToNextBranch()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $sport = $repo->findOneBy(['title' => 'Sports']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $sport->setParent($food);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(12, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        $this->assertEquals(6, $node->getLeft());
        $this->assertEquals(11, $node->getRight());
    }

    public function testTreeUpdateShiftToRoot()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $vegies->setParent(null);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(4, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(5, $node->getRight());
    }

    public function testTreeUpdateShiftToOtherParent()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneBy(['title' => 'Carrots']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $carrots->setParent($food);
        $this->em->persist($carrots);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(8, $node->getRight());
    }

    public function testTreeUpdateShiftToChildParent()
    {
        $this->expectException('UnexpectedValueException');
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $food->setParent($vegies);
        $this->em->persist($food);
        $this->em->flush();
        $this->em->clear();
    }

    public function testTwoUpdateOperations()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $sport = $repo->findOneBy(['title' => 'Sports']);
        $food = $repo->findOneBy(['title' => 'Food']);
        $sport->setParent($food);

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $vegies->setParent(null);

        $this->em->persist($vegies);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Carrots']);

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());
    }

    public function testRemoval()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $this->em->remove($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testTreeWithRootPointingAtAnotherTable()
    {
        // depopulate, i don't want the other stuff in db
        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository('Tree\\Fixture\\ForeignRootCategory');
        $all = $repo->findAll();
        foreach ($all as $one) {
            $this->em->remove($one);
        }
        $this->em->flush();

        $fiction = new ForeignRootCategory();
        $fiction->setTitle('Fiction Books');
        $fiction->setRoot(1);  // Lets pretend this points to another table, and root id 1 is "Books"

        $fact = new ForeignRootCategory();
        $fact->setTitle('Fact Books');
        $fact->setRoot(1);

        $action = new ForeignRootCategory();
        $action->setTitle('Action');
        $action->setRoot(2); // Lets pretend this points to another table, and root id 2 is "Movies"

        $comedy = new ForeignRootCategory();
        $comedy->setTitle('Comedy');
        $comedy->setRoot(2);

        $horror = new ForeignRootCategory();
        $horror->setTitle('Horror');
        $horror->setRoot(2);

        // Child categories now
        $lotr = new ForeignRootCategory();
        $lotr->setTitle('Lord of the Rings');
        $lotr->setParent($fiction);
        $lotr->setRoot(1);

        $warlock = new ForeignRootCategory();
        $warlock->setTitle('The Warlock of Firetop Mountain');
        $warlock->setParent($fiction);
        $warlock->setRoot(1);

        $php = new ForeignRootCategory();
        $php->setTitle('PHP open source development');
        $php->setParent($fact);
        $php->setRoot(1);

        $dracula = new ForeignRootCategory();
        $dracula->setTitle('Hammer Horror Dracula');
        $dracula->setParent($horror);
        $dracula->setRoot(2);

        $frankenstein = new ForeignRootCategory();
        $frankenstein->setTitle('Hammer Horror Frankenstein');
        $frankenstein->setParent($horror);
        $frankenstein->setRoot(2);

        $this->em->persist($fact);
        $this->em->persist($fiction);
        $this->em->persist($comedy);
        $this->em->persist($horror);
        $this->em->persist($action);
        $this->em->persist($lotr);
        $this->em->persist($warlock);
        $this->em->persist($php);
        $this->em->persist($dracula);
        $this->em->persist($frankenstein);
        $this->em->flush();

        $this->assertEquals(1, $fact->getLeft());
        $this->assertEquals(4, $fact->getRight());
        $this->assertEquals(0, $fact->getLevel());
        $this->assertEquals(1, $fact->getRoot());
        $this->assertNull($fact->getParent());

        $this->assertEquals(5, $fiction->getLeft());
        $this->assertEquals(10, $fiction->getRight());
        $this->assertEquals(0, $fiction->getLevel());
        $this->assertEquals(1, $fiction->getRoot());
        $this->assertNull($fiction->getParent());

        $this->assertEquals(6, $lotr->getLeft());
        $this->assertEquals(7, $lotr->getRight());
        $this->assertEquals(1, $lotr->getLevel());
        $this->assertEquals(1, $lotr->getRoot());
        $this->assertEquals($fiction, $lotr->getParent());

        $this->assertEquals(8, $warlock->getLeft());
        $this->assertEquals(9, $warlock->getRight());
        $this->assertEquals(1, $warlock->getLevel());
        $this->assertEquals(1, $warlock->getRoot());
        $this->assertEquals($fiction, $warlock->getParent());

        $this->assertEquals(2, $php->getLeft());
        $this->assertEquals(3, $php->getRight());
        $this->assertEquals(1, $php->getLevel());
        $this->assertEquals(1, $php->getRoot());
        $this->assertEquals($fact, $php->getParent());

        $this->assertEquals(1, $comedy->getLeft());
        $this->assertEquals(2, $comedy->getRight());
        $this->assertEquals(0, $comedy->getLevel());
        $this->assertEquals(2, $comedy->getRoot());
        $this->assertNull($comedy->getParent());

        $this->assertEquals(3, $horror->getLeft());
        $this->assertEquals(8, $horror->getRight());
        $this->assertEquals(0, $horror->getLevel());
        $this->assertEquals(2, $horror->getRoot());
        $this->assertNull($horror->getParent());

        $this->assertEquals(9, $action->getLeft());
        $this->assertEquals(10, $action->getRight());
        $this->assertEquals(0, $action->getLevel());
        $this->assertEquals(2, $action->getRoot());
        $this->assertNull($action->getParent());

        $this->assertEquals(4, $dracula->getLeft());
        $this->assertEquals(5, $dracula->getRight());
        $this->assertEquals(1, $dracula->getLevel());
        $this->assertEquals(2, $dracula->getRoot());
        $this->assertEquals($horror, $dracula->getParent());

        $this->assertEquals(6, $frankenstein->getLeft());
        $this->assertEquals(7, $frankenstein->getRight());
        $this->assertEquals(1, $frankenstein->getLevel());
        $this->assertEquals(2, $frankenstein->getRoot());
        $this->assertEquals($horror, $frankenstein->getParent());

        // Now move the action movie category up
        $repo->moveUp($action);

        $this->assertEquals(1, $comedy->getLeft());
        $this->assertEquals(2, $comedy->getRight());
        $this->assertEquals(0, $comedy->getLevel());
        $this->assertEquals(2, $comedy->getRoot());
        $this->assertNull($comedy->getParent());

        $this->assertEquals(3, $action->getLeft());
        $this->assertEquals(4, $action->getRight());
        $this->assertEquals(0, $action->getLevel());
        $this->assertEquals(2, $action->getRoot());
        $this->assertNull($action->getParent());

        $this->assertEquals(5, $horror->getLeft());
        $this->assertEquals(10, $horror->getRight());
        $this->assertEquals(0, $horror->getLevel());
        $this->assertEquals(2, $horror->getRoot());
        $this->assertNull($horror->getParent());

        $this->assertEquals(6, $dracula->getLeft());
        $this->assertEquals(7, $dracula->getRight());
        $this->assertEquals(1, $dracula->getLevel());
        $this->assertEquals(2, $dracula->getRoot());
        $this->assertEquals($horror, $dracula->getParent());

        $this->assertEquals(8, $frankenstein->getLeft());
        $this->assertEquals(9, $frankenstein->getRight());
        $this->assertEquals(1, $frankenstein->getLevel());
        $this->assertEquals(2, $frankenstein->getRoot());
        $this->assertEquals($horror, $frankenstein->getParent());

        $this->em->clear();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            'Tree\\Fixture\\ForeignRootCategory',
        ];
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
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
        $this->em->clear();
    }
}
