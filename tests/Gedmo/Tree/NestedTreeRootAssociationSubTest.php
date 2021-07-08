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
        $expectedTree = [
            // 'Food'
            'Fruits' => [
                'left' => 1,
                'right' => 4,
                'children' => [
                    'Apples' => [
                        'left' => 2,
                        'right' => 3,
                    ],
                ],
            ],
            'Berries' => [
                'left' => 5,
                'right' => 10,
                'children' => [
                    'Raspberry' => [
                        'left' => 6,
                        'right' => 7,
                    ],
                    'Blueberries' => [
                        'left' => 8,
                        'right' => 9,
                    ],
                ],
            ],
            // 'Sports'
            'Summer' => [
                'left' => 1,
                'right' => 6,
                'children' => [
                    'Basketball' => [
                        'left' => 2,
                        'right' => 3,
                    ],
                    'Football' => [
                        'left' => 4,
                        'right' => 5,
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedTree, $this->collectLeafs($expectedTree));
    }

    private function collectLeafs(array $leafs): array
    {
        $repo = $this->em->getRepository(self::SUB_CATEGORY);

        $collected = [];

        foreach ($leafs as $title => $options) {
            /** @var \Tree\Fixture\RootAssociationSubCategory $item */
            $item = $repo->findOneBy(\compact('title'));
            $collected[$title] = ['left' => $item->getLeft(), 'right' => $item->getRight()];

            if (isset($options['children'])) {
                $collected[$title]['children'] = $this->collectLeafs($options['children']);
            }
        }

        return $collected;
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

        // left 5 right 10
        $berries = new RootAssociationSubCategory();
        $berries->setTitle('Berries');
        $berries->setRoot($food);

        // left 6 right 7
        $raspberry = new RootAssociationSubCategory();
        $raspberry->setTitle('Raspberry');
        $raspberry->setParent($berries);

        // left 8 right 9
        $blueberries = new RootAssociationSubCategory();
        $blueberries->setTitle('Blueberries');
        $blueberries->setParent($berries);

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
        $this->em->persist($berries);
        $this->em->persist($raspberry);
        $this->em->persist($blueberries);
        $this->em->persist($winter);
        $this->em->persist($basketball);
        $this->em->persist($football);
        $this->em->flush();
        $this->em->clear();
    }
}
