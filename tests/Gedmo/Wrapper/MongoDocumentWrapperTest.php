<?php

namespace Wrapper;

use Doctrine\Common\EventManager;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Tool\BaseTestCaseMongoODM;
use Wrapper\Fixture\Document\Article;

/**
 * Mongo Document wrapper tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MongoDocumentWrapperTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Wrapper\\Fixture\\Document\\Article';
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getMockDocumentManager(new EventManager());
        $this->populate();
    }

    public function testManaged()
    {
        $test = $this->dm->find(self::ARTICLE, $this->articleId);
        $this->assertInstanceOf(self::ARTICLE, $test);
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $this->assertEquals($this->articleId, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
        $wrapped->setPropertyValue('title', 'changed');
        $this->assertEquals('changed', $wrapped->getPropertyValue('title'));

        $this->assertTrue($wrapped->hasValidIdentifier());
    }

    public function testProxy()
    {
        $this->dm->clear();
        $test = $this->dm->getReference(self::ARTICLE, $this->articleId);
        $this->assertStringStartsWith('Proxy', get_class($test));
        $this->assertInstanceOf(self::ARTICLE, $test);
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $id = $wrapped->getIdentifier(false);
        $this->assertEquals($this->articleId, $id);

        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedEntity()
    {
        $test = $this->dm->find(self::ARTICLE, $this->articleId);
        $this->dm->clear();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $this->assertEquals($this->articleId, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testDetachedProxy()
    {
        $test = $this->dm->getReference(self::ARTICLE, $this->articleId);
        $this->dm->clear();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $this->assertEquals($this->articleId, $wrapped->getIdentifier());
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));
    }

    public function testSomeFunctions()
    {
        $test = new Article();
        $wrapped = new MongoDocumentWrapper($test, $this->dm);

        $wrapped->populate(['title' => 'test']);
        $this->assertEquals('test', $wrapped->getPropertyValue('title'));

        $this->assertFalse($wrapped->hasValidIdentifier());
    }

    private function populate()
    {
        $test = new Article();
        $test->setTitle('test');
        $this->dm->persist($test);
        $this->dm->flush();
        $this->articleId = $test->getId();
    }
}
