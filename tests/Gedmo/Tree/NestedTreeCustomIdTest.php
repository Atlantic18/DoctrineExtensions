<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Tool\BaseTestCaseORM;
use Tree\Fixture\CustomIdCategory;

/**
 * Test Tree behaviour with a custom Doctrine type for the ID property.
 *
 * @author Paul Dugas <paul@dugasent.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeCustomIdTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);

        // Using a real-world example of Ramsey's binary UUID type. We added
        // the type up in tests/bootstrap.php.
        $this->em->getConnection()
             ->getDatabasePlatform()
             ->registerDoctrineTypeMapping('uuid_binary', 'binary');
    }

    public function testTree() 
    {
        /** @var NestedTreeRepository */
        $repo = $this->em->getRepository(CustomIdCategory::class);

        // Create a root node, "root1"
        $root1 = new CustomIdCategory();
        $root1->setTitle('root1');
        $this->em->persist($root1);
        $this->em->flush();
        $this->em->clear();

        // Should be able to read it back by title 
        $node = $repo->findOneByTitle($root1->getTitle());
        $this->assertNotNull($node);
        $this->assertInstanceOf(CustomIdCategory::class, $node);
        $this->assertSame($root1->getTitle(), $node->getTitle());
        $this->assertEquals($root1->getId(), $node->getId());

        // Should be able to read it back by ID 
        $this->em->clear();
        $node = $repo->findOneById($root1->getId());
        $this->assertNotNull($node);
        $this->assertInstanceOf(CustomIdCategory::class, $node);
        $this->assertSame('root1', $node->getTitle());
        $this->assertEquals($root1->getId(), $node->getId());

        // The tree properties should be correct; $parent is null, $root is
        // itself, $level is 0, $left is 1 and $right is 2.
        $this->assertNull($node->getParent());
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());

        // Create another root node, "root2" so we now have this
        //
        //     - $root1
        //     - $root2
        //
        $this->em->clear();
        $root2 = new CustomIdCategory();
        $root2->setTitle('root2');
        $this->em->persist($root2);
        $this->em->flush();
        $this->em->clear();
        $node = $repo->findOneById($root1->getId());
        $this->assertNull($node->getParent());
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
        $node = $repo->findOneById($root2->getId());
        $this->assertNull($node->getParent());
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());

        // Create a child of $root2 so we now have this
        //
        //     - $root1
        //     - $root2
        //         - $child1
        //
        $this->em->clear();
        $parent = $repo->findOneById($root2->getId());
        $this->assertNotNull($parent);
        $child1 = new CustomIdCategory();
        $child1->setTitle('child1');
        $child1->setParent($parent);
        $this->em->persist($child1);
        $this->em->flush();
        $this->em->clear();
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child1->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child1->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($root2->getId(), $node->getParent()->getId());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(3, $node->getRight());
        $this->assertCount(0, $node->getChildren());

        // Create a child of $child1 so we now have this
        //
        //     - $root1
        //     - $root2
        //         - $child1
        //             -$child2
        //
        $this->em->clear();
        $parent = $repo->findOneById($child1->getId());
        $this->assertNotNull($parent);
        $child2 = new CustomIdCategory();
        $child2->setTitle('child2');
        $child2->setParent($parent);
        $this->em->persist($child2);
        $this->em->flush();
        $this->em->clear();
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(6, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child1->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child1->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($root2->getId(), $node->getParent()->getId());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(5, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child2->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child1->getId(), $node->getParent()->getId());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(3, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
        $this->assertCount(0, $node->getChildren());

        // Create another child of $child1 so we now have this
        //
        //     - $root1
        //     - $root2
        //         - $child1
        //             -$child2
        //             -$child3
        //
        $this->em->clear();
        $parent = $repo->findOneById($child1->getId());
        $this->assertNotNull($parent);
        $child3 = new CustomIdCategory();
        $child3->setTitle('child3');
        $child3->setParent($parent);
        $this->em->persist($child3);
        $this->em->flush();
        $this->em->clear();
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(8, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child1->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child1->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($root2->getId(), $node->getParent()->getId());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(7, $node->getRight());
        $this->assertCount(2, $node->getChildren());
        $this->assertEquals($child2->getId(), $node->getChildren()[0]->getId());
        $this->assertEquals($child3->getId(), $node->getChildren()[1]->getId());
        $node = $repo->findOneById($child2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child1->getId(), $node->getParent()->getId());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(3, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
        $this->assertCount(0, $node->getChildren());
        $node = $repo->findOneById($child3->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child1->getId(), $node->getParent()->getId());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(6, $node->getRight());
        $this->assertCount(0, $node->getChildren());

        // Create a child of $child3 so we now have this
        //
        //     - $root1
        //     - $root2
        //         - $child1
        //             -$child2
        //             -$child3
        //                 -$child4
        //
        $this->em->clear();
        $parent = $repo->findOneById($child3->getId());
        $this->assertNotNull($parent);
        $child4 = new CustomIdCategory();
        $child4->setTitle('child4');
        $child4->setParent($parent);
        $this->em->persist($child4);
        $this->em->flush();
        $this->em->clear();
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(10, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child1->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child1->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($root2->getId(), $node->getParent()->getId());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(9, $node->getRight());
        $this->assertCount(2, $node->getChildren());
        $this->assertEquals($child2->getId(), $node->getChildren()[0]->getId());
        $this->assertEquals($child3->getId(), $node->getChildren()[1]->getId());
        $node = $repo->findOneById($child2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child1->getId(), $node->getParent()->getId());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(3, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
        $this->assertCount(0, $node->getChildren());
        $node = $repo->findOneById($child3->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child1->getId(), $node->getParent()->getId());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(8, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child4->getId(), $node->getChildren()[0]->getId());
        $node = $repo->findOneById($child4->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($root2->getId(), $node->getRoot()->getId());
        $this->assertEquals($child3->getId(), $node->getParent()->getId());
        $this->assertEquals(3, $node->getLevel());
        $this->assertEquals(6, $node->getLeft());
        $this->assertEquals(7, $node->getRight());
        $this->assertCount(0, $node->getChildren());

        // Remove $child1, should end up with this
        //
        //     - $root1
        //     - $root2
        //         -$child2
        //         -$child3
        //             -$child4
        //
        $this->em->clear();
        $node = $repo->findOneById($child1->getId());
        $this->assertNotNull($node);
        $repo->removeFromTree($node);
        $this->em->clear();
        $node = $repo->findOneById($child1->getId());
        $this->assertTrue($node === null);
        $this->assertNull($node);
        $node = $repo->findOneById($root1->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
        $this->assertCount(0, $node->getChildren());
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(8, $node->getRight());
        $this->assertCount(2, $node->getChildren());
        $this->assertEquals($child2->getId(), $node->getChildren()[0]->getId());
        $this->assertEquals($child3->getId(), $node->getChildren()[1]->getId());

        // Remove $root1 and can't find it any more
        $node = $repo->findOneById($root1->getId());
        $this->assertNotNull($node);
        $repo->removeFromTree($node);
        $this->em->clear();
        $node = $repo->findOneById($root1->getId());
        $this->assertNull($node);

        // Can still find $root2, remove it, can't find it then
        $node = $repo->findOneById($root2->getId());
        $this->assertNotNull($node);
        $repo->removeFromTree($node);
        $this->em->clear();
        $node = $repo->findOneById($root2->getId());
        $this->assertNull($node);

        // $child2 should have been promoted to be a root
        $node = $repo->findOneById($child2->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
        $this->assertCount(0, $node->getChildren());

        // $child3 should have been promoted to be a root
        $node = $repo->findOneById($child3->getId());
        $this->assertNotNull($node);
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
        $this->assertCount(1, $node->getChildren());
        $this->assertEquals($child4->getId(), $node->getChildren()[0]->getId());
    }

    protected function getUsedEntityFixtures()
    {
        return array(CustomIdCategory::class);
    }
}
