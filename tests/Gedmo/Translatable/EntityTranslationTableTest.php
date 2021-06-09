<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Person;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityTranslationTableTest extends BaseTestCaseORM
{
    public const PERSON = 'Translatable\\Fixture\\Person';
    public const TRANSLATION = 'Translatable\\Fixture\\PersonTranslation';

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
    }

    public function testFixtureGeneratedTranslations()
    {
        $person = new Person();
        $person->setName('name in en');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $translations = $repo->findTranslations($person);
        //As Translate locale and Default locale are the same, no records should be present in translations table
        $this->assertCount(0, $translations);

        // test second translations
        $person = $this->em->find(self::PERSON, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($person);
        //Only one translation should be present
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);

        $this->translatableListener->setTranslatableLocale('en_us');
    }

    /**
     * Covers issue #438
     *
     * @test
     */
    public function shouldPersistDefaultLocaleValue()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setTranslatableLocale('de');
        $person = new Person();
        $person->setName('de');

        $repo = $this->em->getRepository(self::TRANSLATION);
        $repo
            ->translate($person, 'name', 'de', 'de')
            ->translate($person, 'name', 'en_us', 'en_us')
        ;
        $this->em->persist($person);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('en_us');
        $articles = $this->em->createQuery('SELECT p FROM '.self::PERSON.' p')->getArrayResult();
        $this->assertEquals('en_us', $articles[0]['name']);
        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
        $this->assertCount(2, $trans);
        foreach ($trans as $item) {
            $this->assertEquals($item['locale'], $item['content']);
        }
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON,
            self::TRANSLATION,
        ];
    }
}
