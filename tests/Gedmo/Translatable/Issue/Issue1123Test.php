<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Issue1123\ChildEntity;

class Issue1123Test extends BaseTestCaseORM
{
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    public const BASE_ENTITY = 'Translatable\\Fixture\\Issue1123\\BaseEntity';
    public const CHILD_ENTITY = 'Translatable\\Fixture\\Issue1123\\ChildEntity';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldFindInheritedClassTranslations()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);

        $title = 'Hello World';
        $deTitle = 'Hallo Welt';

        // Check that the child class can have translations
        $childEntity = new ChildEntity();
        $childEntity->setChildTitle($title);
        $this->em->persist($childEntity);
        $this->em->flush();

        $childEntity->setTranslatableLocale('de');
        $childEntity->setChildTitle($deTitle);
        $this->em->persist($childEntity);
        $this->em->flush();

        // Clear to be sure...
        $this->em->clear();

        // Find using the repository
        $translations = $repo->findTranslations($childEntity);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de', $translations);
        $this->assertSame(['childTitle' => $deTitle], $translations['de']);

        // find using QueryBuilder
        $qb = $this->em->createQueryBuilder()->select('e')->from(self::CHILD_ENTITY, 'e');

        $query = $qb->getQuery();
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\Translatable\Query\TreeWalker\TranslationWalker');
        $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'de');
        $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1);

        $res = $query->getArrayResult();
        $this->assertArrayHasKey('id', $res[0]);
        $this->assertArrayHasKey('childTitle', $res[0]);
        $this->assertArrayHasKey('discr', $res[0]);
        $this->assertSame(1, $res[0]['id']);
        $this->assertSame($deTitle, $res[0]['childTitle']);
        $this->assertSame('child', $res[0]['discr']);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TRANSLATION,
            self::BASE_ENTITY,
            self::CHILD_ENTITY,
        ];
    }
}
