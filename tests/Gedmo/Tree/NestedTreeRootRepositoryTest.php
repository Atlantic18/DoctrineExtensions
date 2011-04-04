<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Tree\Fixture\RootCategory";
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Tree\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $treeListener = new TreeListener();
        $evm->addEventSubscriber($treeListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS)
        ));
        
        $this->populate();
    }
    
    public function testRepository()
    {        
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        $carrots = $repo->findOneByTitle('Carrots');
        
        $path = $repo->getPath($carrots);
        $this->assertEquals(3, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());
        
        $vegies = $repo->findOneByTitle('Vegitables');
        $childCount = $repo->childCount($vegies);
        $this->assertEquals(2, $childCount);
        
        $food = $repo->findOneByTitle('Food');
        $childCount = $repo->childCount($food, true);
        $this->assertEquals(2, $childCount);
        
        $childCount = $repo->childCount($food);
        $this->assertEquals(4, $childCount);
        
        $childCount = $repo->childCount();
        $this->assertEquals(6, $childCount);
    }
    
    public function testAdvancedRepositoryFunctions()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        
        // verification
        
        $this->assertTrue($repo->verify());
        
        $dql = 'UPDATE ' . self::TEST_ENTITY_CLASS . ' node';
        $dql .= ' SET node.lft = 1';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();
        
        $this->em->clear(); // must clear cached entities
        $errors = $repo->verify();
        $this->assertEquals(2, count($errors));
        $this->assertEquals('index [1], duplicate on tree root: 1', $errors[0]);
        $this->assertEquals('index [4], missing on tree root: 1', $errors[1]);
        
        $dql = 'UPDATE ' . self::TEST_ENTITY_CLASS . ' node';
        $dql .= ' SET node.lft = 4';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();
        
        //@todo implement
        /*$this->em->clear();
        $repo->recover();
        $this->em->clear();
        $this->assertTrue($repo->verify());*/
        
        $this->em->clear();
        $onions = $repo->findOneByTitle('Onions');
        
        $this->assertEquals(11, $onions->getLeft());
        $this->assertEquals(12, $onions->getRight());
        
        // move up
        
        $repo->moveUp($onions);
        $this->em->refresh($onions);
        
        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());
        
        $repo->moveUp($onions, true);
        $this->em->refresh($onions);
        
        $this->assertEquals(5, $onions->getLeft());
        $this->assertEquals(6, $onions->getRight());
        
        // move down
        
        $repo->moveDown($onions, 2);
        $this->em->refresh($onions);
        
        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());
        
        // reorder
        
        $this->em->clear();
        $node = $repo->findOneByTitle('Food');
        $repo->reorder($node, 'title', 'ASC', false);
        
        $this->em->clear();
        
        $node = $repo->findOneByTitle('Cabbages');
        
        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(6, $node->getRight());
        
        $node = $repo->findOneByTitle('Carrots');
        
        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(8, $node->getRight());
        
        $node = $repo->findOneByTitle('Onions');
        
        $this->assertEquals(9, $node->getLeft());
        $this->assertEquals(10, $node->getRight());
        
        $node = $repo->findOneByTitle('Potatoes');
        
        $this->assertEquals(11, $node->getLeft());
        $this->assertEquals(12, $node->getRight());
        
        // leafs
        
        $leafs = $repo->getLeafs($node);
        $this->assertEquals(5, count($leafs));
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Cabbages', $leafs[1]->getTitle());
        $this->assertEquals('Carrots', $leafs[2]->getTitle());
        $this->assertEquals('Onions', $leafs[3]->getTitle());
        $this->assertEquals('Potatoes', $leafs[4]->getTitle());
        
        // remove
        
        $this->em->clear();
        $node = $repo->findOneByTitle('Fruits');
        $id = $node->getId();
        $repo->removeFromTree($node);
        
        $this->assertTrue(is_null($repo->find($id)));
        
        $node = $repo->findOneByTitle('Vegitables');
        $id = $node->getId();
        $repo->removeFromTree($node);
        
        $this->assertTrue(is_null($repo->find($id)));
        $this->em->clear();
        
        $node = $repo->findOneByTitle('Cabbages');
        
        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(1, $node->getParent()->getId());
    }
    
    private function populateMore()
    {
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CLASS)
            ->findOneByTitle('Vegitables');
            
        $cabbages = new RootCategory();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');
        
        $onions = new RootCategory();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');
        
        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
        $this->em->clear();
    }
    
    private function populate()
    {
        $root = new RootCategory();
        $root->setTitle("Food");
        
        $root2 = new RootCategory();
        $root2->setTitle("Sports");
        
        $child = new RootCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);
        
        $child2 = new RootCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        
        $childsChild = new RootCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        
        $potatoes = new RootCategory();
        $potatoes->setTitle("Potatoes");
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
