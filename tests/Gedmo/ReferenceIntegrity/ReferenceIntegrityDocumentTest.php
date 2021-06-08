<?php

namespace Gedmo\ReferenceIntegrity;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for the ReferenceIntegrity extension
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ReferenceIntegrityDocumentTest extends BaseTestCaseMongoODM
{
    const TYPE_ONE_NULLIFY_CLASS = 'ReferenceIntegrity\Fixture\Document\OneNullify\Type';
    const ARTICLE_ONE_NULLIFY_CLASS = 'ReferenceIntegrity\Fixture\Document\OneNullify\Article';

    const TYPE_MANY_NULLIFY_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyNullify\Type';
    const ARTICLE_MANY_NULLIFY_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyNullify\Article';

    const TYPE_ONE_PULL_CLASS = 'ReferenceIntegrity\Fixture\Document\OnePull\Type';
    const ARTICLE_ONE_PULL_CLASS = 'ReferenceIntegrity\Fixture\Document\OnePull\Article';

    const TYPE_MANY_PULL_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyPull\Type';
    const ARTICLE_MANY_PULL_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyPull\Article';

    const TYPE_ONE_RESTRICT_CLASS = 'ReferenceIntegrity\Fixture\Document\OneRestrict\Type';
    const ARTICLE_ONE_RESTRICT_CLASS = 'ReferenceIntegrity\Fixture\Document\OneRestrict\Article';

    const TYPE_MANY_RESTRICT_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyRestrict\Type';
    const ARTICLE_MANY_RESTRICT_CLASS = 'ReferenceIntegrity\Fixture\Document\ManyRestrict\Article';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new ReferenceIntegrityListener());

        $this->dm = $this->getMockDocumentManager($evm, $this->getMockAnnotatedConfig());

        $this->populateOneNullify();
        $this->populateManyNullify();

        $this->populateOnePull();
        $this->populateManyPull();

        $this->populateOneRestrict();
        $this->populateManyRestrict();
    }

    public function testOneNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Type']);

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Type']);
        $this->assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_ONE_NULLIFY_CLASS)
            ->findOneBy(['title' => 'One Nullify Article']);

        $this->assertNull($article->getType());

        $this->dm->clear();
    }

    public function testManyNullify()
    {
        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Type']);

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();

        $type = $this->dm->getRepository(self::TYPE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Type']);
        $this->assertNull($type);

        $article = $this->dm->getRepository(self::ARTICLE_MANY_NULLIFY_CLASS)
            ->findOneBy(['title' => 'Many Nullify Article']);

        $this->assertNull($article->getType());

        $this->dm->clear();
    }

    public function testOnePull()
    {
        $type1 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 1']);
        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 2']);

        $this->assertFalse(is_null($type1));
        $this->assertTrue(is_object($type1));

        $this->assertFalse(is_null($type2));
        $this->assertTrue(is_object($type2));

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Type 2']);
        $this->assertNull($type2);

        $article = $this->dm->getRepository(self::ARTICLE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'One Pull Article']);

        $types = $article->getTypes();
        $this->assertTrue(1 === (is_array($types) || $types instanceof \Countable ? count($types) : 0));
        $this->assertEquals('One Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    public function testManyPull()
    {
        $type1 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 1']);
        $type2 = $this->dm->getRepository(self::TYPE_ONE_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 2']);

        $this->assertFalse(is_null($type1));
        $this->assertTrue(is_object($type1));

        $this->assertFalse(is_null($type2));
        $this->assertTrue(is_object($type2));

        $this->dm->remove($type2);
        $this->dm->flush();

        $type2 = $this->dm->getRepository(self::TYPE_MANY_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Type 2']);
        $this->assertNull($type2);

        $article = $this->dm->getRepository(self::ARTICLE_MANY_PULL_CLASS)
            ->findOneBy(['title' => 'Many Pull Article']);

        $types = $article->getTypes();
        $this->assertTrue(1 === (is_array($types) || $types instanceof \Countable ? count($types) : 0));
        $this->assertEquals('Many Pull Type 1', $types[0]->getTitle());

        $this->dm->clear();
    }

    /**
     * @test
     */
    public function testOneRestrict()
    {
        $this->expectException('Gedmo\Exception\ReferenceIntegrityStrictException');
        $type = $this->dm->getRepository(self::TYPE_ONE_RESTRICT_CLASS)
            ->findOneBy(['title' => 'One Restrict Type']);

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();
    }

    /**
     * @test
     */
    public function testManyRestrict()
    {
        $this->expectException('Gedmo\Exception\ReferenceIntegrityStrictException');
        $type = $this->dm->getRepository(self::TYPE_MANY_RESTRICT_CLASS)
            ->findOneBy(['title' => 'Many Restrict Type']);

        $this->assertFalse(is_null($type));
        $this->assertTrue(is_object($type));

        $this->dm->remove($type);
        $this->dm->flush();
    }

    private function populateOneNullify()
    {
        $typeClass = self::TYPE_ONE_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Nullify Type');

        $articleClass = self::ARTICLE_ONE_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyNullify()
    {
        $typeClass = self::TYPE_MANY_NULLIFY_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Nullify Type');

        $articleClass = self::ARTICLE_MANY_NULLIFY_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Nullify Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOnePull()
    {
        $typeClass = self::TYPE_ONE_PULL_CLASS;
        $type1 = new $typeClass();
        $type1->setTitle('One Pull Type 1');

        $type2 = new $typeClass();
        $type2->setTitle('One Pull Type 2');

        $articleClass = self::ARTICLE_ONE_PULL_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Pull Article');
        $article->addType($type1);
        $article->addType($type2);

        $this->dm->persist($article);
        $this->dm->persist($type1);
        $this->dm->persist($type2);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyPull()
    {
        $typeClass = self::TYPE_MANY_PULL_CLASS;
        $type1 = new $typeClass();
        $type1->setTitle('Many Pull Type 1');

        $type2 = new $typeClass();
        $type2->setTitle('Many Pull Type 2');

        $articleClass = self::ARTICLE_MANY_PULL_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Pull Article');
        $article->addType($type1);
        $article->addType($type2);

        $this->dm->persist($article);
        $this->dm->persist($type1);
        $this->dm->persist($type2);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateOneRestrict()
    {
        $typeClass = self::TYPE_ONE_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('One Restrict Type');

        $articleClass = self::ARTICLE_ONE_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('One Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }

    private function populateManyRestrict()
    {
        $typeClass = self::TYPE_MANY_RESTRICT_CLASS;
        $type = new $typeClass();
        $type->setTitle('Many Restrict Type');

        $articleClass = self::ARTICLE_MANY_RESTRICT_CLASS;
        $article = new $articleClass();
        $article->setTitle('Many Restrict Article');
        $article->setType($type);

        $this->dm->persist($article);
        $this->dm->persist($type);

        $this->dm->flush();
        $this->dm->clear();
    }
}
