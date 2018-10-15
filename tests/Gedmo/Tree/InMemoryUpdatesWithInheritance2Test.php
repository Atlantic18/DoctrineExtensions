<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Concept\Association;
use Tree\Fixture\Concept\Shop;

/**
 * Additional tests for tree inheritance and in-memory updates
 *
 * @author Pierre-Yves CARIOU <cariou.p@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InMemoryUpdatesWithInheritance2Test extends BaseTestCaseORM
{
    const CONCEPT = "Tree\\Fixture\\Concept\\Concept";
    const SHOP = "Tree\\Fixture\\Concept\\Shop";
    const ASSOCIATION = "Tree\\Fixture\\Concept\\Association";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testInMemoryTreeInsertsWithInheritance()
    {
        $shopRoot = new Shop('Shop -Root');
        $this->em->persist($shopRoot);

        $shopChild = new Shop('Shop - Child');
        $shopChild->setParent($shopRoot);
        $shopChild->setRoot($shopRoot);
        $this->em->persist($shopChild);

        $associationRoot = new Association('Association -Root');
        $this->em->persist($associationRoot);

        $associationChild = new Association('Association - Child');
        $associationChild->setParent($associationRoot);
        $associationChild->setRoot($associationRoot);
        $this->em->persist($associationChild);

        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CONCEPT,
            self::ASSOCIATION,
            self::SHOP,
        );
    }
}
