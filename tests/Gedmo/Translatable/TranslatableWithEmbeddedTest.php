<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Company;

class TranslatableWithEmbeddedTest extends BaseTestCaseORM
{
    public const FIXTURE = 'Translatable\\Fixture\\Company';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    public const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function populate()
    {
        $entity = new Company();
        $entity->setTitle('test');
        $entity->getLink()->setWebsite('website');
        $entity->getLink()->setFacebook('facebook');

        $this->em->persist($entity);
        $this->em->flush();

        $entity->setTranslatableLocale('de');
        $entity->setTitle('test-de');
        $entity->getLink()->setWebsite('website-de');
        $entity->getLink()->setFacebook('facebook-de');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @test
     */
    public function testTranslate()
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository(self::FIXTURE);

        /** @var Company $entity */
        $entity = $repo->findOneBy(['id' => 1]);

        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($entity);

        $this->assertArrayHasKey('de', $translations);
        $this->assertSame('test-de', $translations['de']['title']);
        $this->assertSame('test', $entity->getTitle());

        $this->assertSame('website-de', $translations['de']['link.website']);
        $this->assertSame('website', $entity->getLink()->getWebsite());

        $this->assertSame('facebook-de', $translations['de']['link.facebook']);
        $this->assertSame('facebook', $entity->getLink()->getFacebook());

        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('de');
        $repo = $this->em->getRepository(self::FIXTURE);
        $entity = $repo->findOneBy(['id' => $entity->getId()]);

        $this->assertSame('website-de', $entity->getLink()->getWebsite());
        $this->assertSame('facebook-de', $entity->getLink()->getFacebook());
    }

    /**
     * @test
     */
    public function testQueryWalker()
    {
        $dql = 'SELECT f FROM '.self::FIXTURE.' f';

        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('de');

        $result = $q->getArrayResult();

        $this->assertCount(1, $result);
        $this->assertSame('test-de', $result[0]['title']);
        $this->assertSame('website-de', $result[0]['link.website']);
        $this->assertSame('facebook-de', $result[0]['link.facebook']);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::FIXTURE,
            self::TRANSLATION,
        ];
    }
}
