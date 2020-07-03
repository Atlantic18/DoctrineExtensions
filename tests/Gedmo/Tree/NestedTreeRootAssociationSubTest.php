<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootAssociationCategory;
use Tree\Fixture\RootAssociationSubCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Valery Vargin <VDVUGaD@gmail.com>
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootAssociationSubTest extends BaseTestCaseORM
{
    public const CATEGORY = RootAssociationCategory::class;
    public const SUB_CATEGORY = RootAssociationSubCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testRootEntity(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $subRepo = $this->em->getRepository(self::SUB_CATEGORY);

        // roots from another table
        /** @var \Tree\Fixture\RootAssociationCategory $food */
        $food = $repo->findOneBy(['title' => 'Food']);
        /** @var \Tree\Fixture\RootAssociationCategory $sports */
        $sports = $repo->findOneBy(['title' => 'Sports']);

        /** @var \Tree\Fixture\RootAssociationSubCategory $fruits */
        $fruits = $subRepo->findOneBy(['title' => 'Fruits']);
        $this->assertEquals($food->getId(), $fruits->getRoot()->getId());

        /** @var \Tree\Fixture\RootAssociationSubCategory $apples */
        $apples = $subRepo->findOneBy(['title' => 'Apples']);
        $this->assertEquals($food->getId(), $apples->getRoot()->getId(), 'Subcategory child should respect parent root');

        /** @var \Tree\Fixture\RootAssociationSubCategory $summer */
        $summer = $subRepo->findOneBy(['title' => 'Summer']);
        $this->assertEquals($sports->getId(), $summer->getRoot()->getId());

        /** @var \Tree\Fixture\RootAssociationSubCategory $basketball */
        $basketball = $subRepo->findOneBy(['title' => 'Basketball']);
        $this->assertEquals($sports->getId(), $basketball->getRoot()->getId());

        /** @var \Tree\Fixture\RootAssociationSubCategory $football */
        $football = $subRepo->findOneBy(['title' => 'Football']);
        $this->assertEquals($sports->getId(), $football->getRoot()->getId());
    }

    public function testPositions(): void
    {
        $repo = $this->em->getRepository(self::SUB_CATEGORY);

        /** @var \Tree\Fixture\RootAssociationSubCategory $fruits */
        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $this->assertEquals(1, $fruits->getLeft());
        $this->assertEquals(4, $fruits->getRight());

        /** @var \Tree\Fixture\RootAssociationSubCategory $apples */
        $apples = $repo->findOneBy(['title' => 'Apples']);
        $this->assertEquals(2, $apples->getLeft());
        $this->assertEquals(3, $apples->getRight());

        /** @var \Tree\Fixture\RootAssociationSubCategory $summer */
        $summer = $repo->findOneBy(['title' => 'Summer']);
        $this->assertEquals(1, $summer->getLeft(), 'Another root, so should started from begin');
        $this->assertEquals(6, $summer->getRight());

        /** @var \Tree\Fixture\RootAssociationSubCategory $basketball */
        $basketball = $repo->findOneBy(['title' => 'Basketball']);
        $this->assertEquals(2, $basketball->getLeft());
        $this->assertEquals(3, $basketball->getRight());

        /** @var \Tree\Fixture\RootAssociationSubCategory $football */
        $football = $repo->findOneBy(['title' => 'Football']);
        $this->assertEquals(4, $football->getLeft());
        $this->assertEquals(5, $football->getRight());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::SUB_CATEGORY,
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     */
    private function populate(): void
    {
        // Base categories stores in one table, sub categories - in another tables
        $food = new RootAssociationCategory();
        $food->setTitle('Food');

        // left 1 right 4
        $fruits = new RootAssociationSubCategory();
        $fruits->setTitle('Fruits');
        $fruits->setRoot($food);

        // left 2 right 3
        $apples = new RootAssociationSubCategory();
        $apples->setTitle('Apples');
        $apples->setParent($fruits);

        // Base category for sports
        $sports = new RootAssociationCategory();
        $sports->setTitle('Sports');

        // left 1 right 6
        $winter = new RootAssociationSubCategory();
        $winter->setTitle('Summer');
        $winter->setRoot($sports);

        // left 2 right 3
        $basketball = new RootAssociationSubCategory();
        $basketball->setTitle('Basketball');
        $basketball->setParent($winter);

        // left 4 right 5
        $football = new RootAssociationSubCategory();
        $football->setTitle('Football');
        $football->setParent($winter);

        $this->em->persist($food);
        $this->em->persist($sports);
        $this->em->persist($fruits);
        $this->em->persist($apples);
        $this->em->persist($winter);
        $this->em->persist($basketball);
        $this->em->persist($football);
        $this->em->flush();
        $this->em->clear();
    }
}
