<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\MixedValue;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MixedValueTranslationTest extends BaseTestCaseORM
{
    public const MIXED = 'Translatable\\Fixture\\MixedValue';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType('custom')) {
            Type::addType('custom', 'Translatable\Fixture\Type\Custom');
        }

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testFixtureGeneratedTranslations()
    {
        $repo = $this->em->getRepository(self::MIXED);
        $mixed = $repo->findOneBy(['id' => 1]);

        $this->assertTrue($mixed->getDate() instanceof \DateTime);
        $this->assertTrue($mixed->getCust() instanceof \stdClass);
        $this->assertEquals('en', $mixed->getCust()->test);
    }

    public function testOtherTranslation()
    {
        $repo = $this->em->getRepository(self::MIXED);
        $mixed = $repo->findOneBy(['id' => 1]);

        $this->translatableListener->setTranslatableLocale('de_de');
        $mixed->setDate(new \DateTime('2000-00-00 00:00:00'));
        $cust = new \stdClass();
        $cust->test = 'de';
        $mixed->setCust($cust);

        $this->em->persist($mixed);
        $this->em->flush();
        $this->em->clear();

        $mixed = $repo->findOneBy(['id' => 1]);
        $transRepo = $this->em->getRepository(self::TRANSLATION);
        $translations = $transRepo->findTranslations($mixed);

        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_de', $translations);
        $cust = unserialize($translations['de_de']['cust']);

        $this->assertTrue($cust instanceof \stdClass);
        $this->assertEquals('de', $cust->test);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::MIXED,
            self::TRANSLATION,
        ];
    }

    private function populate()
    {
        $mixedEn = new MixedValue();
        $mixedEn->setDate(new \DateTime());
        $cust = new \stdClass();
        $cust->test = 'en';
        $mixedEn->setCust($cust);

        $this->em->persist($mixedEn);
        $this->em->flush();
        $this->em->clear();
    }
}
