<?php

namespace Wrapper;

use Doctrine\Common\EventManager;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Tool\BaseTestCaseORM;
use Wrapper\Fixture\Entity\Article;

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
    public const ARTICLE = 'Wrapper\\Fixture\\Entity\\Article';

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
        ];
    }

    private function populate()
    {
        $test = new Article();
        $test->setTitle('test');
        $this->em->persist($test);
        $this->em->flush();
    }
}
