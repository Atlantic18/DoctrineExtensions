<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Tree behavior
 * Based on reported github issue #12
 * JOINED table inheritance mapping bug on Tree;
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultInheritanceWithJoinedTableTest extends BaseTestCaseORM
{
    public const USER = 'Tree\\Fixture\\User';
    public const GROUP = 'Tree\\Fixture\\UserGroup';
    public const ROLE = 'Tree\\Fixture\\Role';
    public const USERLDAP = 'Tree\\Fixture\\UserLDAP';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->tree = new TreeListener();
        $evm->addEventSubscriber($this->tree);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    public function shouldHandleMultilevelInheritance()
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        $adminRight = $admins->getRight();
        $userLdap = new \Tree\Fixture\UserLDAP('testname');
        $userLdap->init();
        $userLdap->setParent($admins);
        $this->em->persist($userLdap);
        $this->em->flush();
        $this->em->clear();

        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        self::assertNotEquals($adminRight, $admins->getRight());
    }

    /**
     * @test
     */
    public function shouldBeAbleToPopulateTree()
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        $user3 = new \Tree\Fixture\User('user3@test.com', 'secret');
        $user3->init();
        $user3->setParent($admins);

        $this->em->persist($user3);
        $this->em->flush();
        $this->em->clear();

        // run tree consistence checks

        $everyBody = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Everybody']);
        $this->assertEquals(1, $everyBody->getLeft());
        $this->assertEquals(14, $everyBody->getRight());
        $this->assertEquals(0, $everyBody->getLevel());

        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        $this->assertEquals(2, $admins->getLeft());
        $this->assertEquals(7, $admins->getRight());
        $this->assertEquals(1, $admins->getLevel());

        $visitors = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Visitors']);
        $this->assertEquals(8, $visitors->getLeft());
        $this->assertEquals(13, $visitors->getRight());
        $this->assertEquals(1, $visitors->getLevel());

        $user0 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user0@test.com']);
        $this->assertEquals(3, $user0->getLeft());
        $this->assertEquals(4, $user0->getRight());
        $this->assertEquals(2, $user0->getLevel());

        $user1 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user1@test.com']);
        $this->assertEquals(9, $user1->getLeft());
        $this->assertEquals(10, $user1->getRight());
        $this->assertEquals(2, $user1->getLevel());

        $user2 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user2@test.com']);
        $this->assertEquals(11, $user2->getLeft());
        $this->assertEquals(12, $user2->getRight());
        $this->assertEquals(2, $user2->getLevel());

        $user3 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user3@test.com']);
        $this->assertEquals(5, $user3->getLeft());
        $this->assertEquals(6, $user3->getRight());
        $this->assertEquals(2, $user3->getLevel());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
            self::GROUP,
            self::ROLE,
            self::USERLDAP,
        ];
    }

    private function populate()
    {
        $everyBody = new \Tree\Fixture\UserGroup('Everybody');
        $admins = new \Tree\Fixture\UserGroup('Admins');
        $admins->setParent($everyBody);
        $visitors = new \Tree\Fixture\UserGroup('Visitors');
        $visitors->setParent($everyBody);

        $user0 = new \Tree\Fixture\User('user0@test.com', 'secret');
        $user0->init();
        $user0->setParent($admins);
        $user1 = new \Tree\Fixture\User('user1@test.com', 'secret');
        $user1->init();
        $user1->setParent($visitors);
        $user2 = new \Tree\Fixture\User('user2@test.com', 'secret');
        $user2->init();
        $user2->setParent($visitors);

        $this->em->persist($everyBody);
        $this->em->persist($admins);
        $this->em->persist($visitors);
        $this->em->persist($user0);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->clear();
    }
}
