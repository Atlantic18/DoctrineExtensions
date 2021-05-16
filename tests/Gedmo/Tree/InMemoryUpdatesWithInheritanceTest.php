<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Genealogy\Man;
use Tree\Fixture\Genealogy\Woman;

/**
 * Additional tests for tree inheritance and in-memory updates
 *
 * @author Illya Klymov <xanf@xanf.me>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InMemoryUpdatesWithInheritanceTest extends BaseTestCaseORM
{
    public const PERSON = 'Tree\\Fixture\\Genealogy\\Person';
    public const MAN = 'Tree\\Fixture\\Genealogy\\Man';
    public const WOMAN = 'Tree\\Fixture\\Genealogy\\Woman';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testInMemoryTreeInsertsWithInheritance()
    {
        $nodes = [];

        $man1 = new Man('Root - Man1');
        $this->em->persist($man1);

        $woman1 = new Woman('Level 1 - Woman1');
        $this->em->persist($woman1);
        $woman1->setParent($man1);

        $man2 = new Man('Level 2 - Man2');
        $this->em->persist($man2);
        $man2->setParent($woman1);

        $woman2 = new Woman('Level 3 - Woman2');
        $this->em->persist($woman2);
        $woman2->setParent($man2);

        $this->em->flush();

        $left = $man1->getLeft();
        $right = $man1->getRight();
        $level = $man1->getLevel();
        $this->assertEquals(1, $left);
        $this->assertEquals(8, $right);
        $this->assertEquals(0, $level);

        $left = $woman1->getLeft();
        $right = $woman1->getRight();
        $level = $woman1->getLevel();
        $this->assertEquals(2, $left);
        $this->assertEquals(7, $right);
        $this->assertEquals(1, $level);

        $left = $man2->getLeft();
        $right = $man2->getRight();
        $level = $man2->getLevel();
        $this->assertEquals(3, $left);
        $this->assertEquals(6, $right);
        $this->assertEquals(2, $level);

        $left = $woman2->getLeft();
        $right = $woman2->getRight();
        $level = $woman2->getLevel();
        $this->assertEquals(4, $left);
        $this->assertEquals(5, $right);
        $this->assertEquals(3, $level);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON,
            self::MAN,
            self::WOMAN,
        ];
    }
}
