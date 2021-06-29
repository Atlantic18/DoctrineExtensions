<?php

namespace Wrapper;

use Doctrine\Common\EventManager;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Tool\BaseTestCaseORM;
use Wrapper\Fixture\Entity\Article;
use Wrapper\Fixture\Entity\Composite;
use Wrapper\Fixture\Entity\CompositeRelation;

/**
 * Entity wrapper tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityWrapperTest extends BaseTestCaseORM
{
    const ARTICLE = 'Wrapper\\Fixture\\Entity\\Article';
    const COMPOSITE = 'Wrapper\\Fixture\\Entity\\Composite';
    const COMPOSITE_RELATION = 'Wrapper\\Fixture\\Entity\\CompositeRelation';

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMockSqliteEntityManager(new EventManager());
        $this->populate();
    }

    public function testManaged()
    {
        $test = $this->em->find(self::ARTICLE, ['id' => 1]);
        $this->assertInstanceOf(self::ARTICLE, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $this->assertEquals(1, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
        $wrapped->setPropertyValue('title', 'changed');
        $this->assertEquals('changed', $wrapped->getPropertyValue('title'));

        $this->assertTrue($wrapped->hasValidIdentifier());
    }

    public function testProxy()
    {
        $this->em->clear();
        $test = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $this->assertInstanceOf('Doctrine\\ORM\\Proxy\\Proxy', $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        $this->assertTrue(is_array($id));
        $this->assertCount(1, $id);
        $this->assertArrayHasKey('id', $id);
        $this->assertEquals(1, $id['id']);

        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testComposite()
    {
        $test = $this->em->getReference(self::COMPOSITE, ['one' => 1, 'two' => 2]);
        $this->assertInstanceOf(self::COMPOSITE, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        $this->assertTrue(is_array($id));
        $this->assertCount(2, $id);
        $this->assertArrayHasKey('one', $id);
        $this->assertArrayHasKey('two', $id);
        $this->assertEquals(1, $id['one']);
        $this->assertEquals(2, $id['two']);

        $id = $wrapped->getIdentifier(false, true);
        $this->assertTrue(is_string($id));
        $this->assertEquals('1 2', $id);

        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testCompositeRelation()
    {
        $art1 = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $test = $this->em->getReference(self::COMPOSITE_RELATION, ['article' => $art1->getId(), 'status' => 2]);
        $this->assertInstanceOf(self::COMPOSITE_RELATION, $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $id = $wrapped->getIdentifier(false);
        $this->assertTrue(is_array($id));
        $this->assertCount(2, $id);
        $this->assertArrayHasKey('article', $id);
        $this->assertArrayHasKey('status', $id);

        $id = $wrapped->getIdentifier(false, true);
        $this->assertTrue(is_string($id));
        $this->assertEquals('1 2', $id);

        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedEntity()
    {
        $test = $this->em->find(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        $this->assertEquals(1, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy()
    {
        $test = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        $this->assertEquals(1, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedCompositeRelation()
    {
        $test = $this->em->getReference(self::COMPOSITE_RELATION, ['article' => 1, 'status' => 2]);
        $this->em->clear();
        $wrapped = new EntityWrapper($test, $this->em);

        $this->assertEquals('1 2', $wrapped->getIdentifier(false, true));
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testCompositeRelationProxy()
    {
        $this->em->clear();
        $art1 = $this->em->getReference(self::ARTICLE, ['id' => 1]);
        $test = $this->em->getReference(self::COMPOSITE_RELATION, ['article' => $art1->getId(), 'status' => 2]);
        $this->assertInstanceOf('Doctrine\\ORM\\Proxy\\Proxy', $test);
        $wrapped = new EntityWrapper($test, $this->em);

        $this->assertEquals('1 2', $wrapped->getIdentifier(false, true));
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testSomeFunctions()
    {
        $test = new Article();
        $wrapped = new EntityWrapper($test, $this->em);

        $wrapped->populate(['title' => 'test']);
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));

        $this->assertFalse($wrapped->hasValidIdentifier());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMPOSITE,
            self::COMPOSITE_RELATION,
        ];
    }

    private function populate()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->em->persist($article);
        $composite = new Composite(1, 2);
        $composite->setTitle('test');
        $this->em->persist($composite);
        $compositeRelation = new CompositeRelation($article, 2);
        $compositeRelation->setTitle('test');
        $this->em->persist($compositeRelation);
        $this->em->flush();
    }
}
