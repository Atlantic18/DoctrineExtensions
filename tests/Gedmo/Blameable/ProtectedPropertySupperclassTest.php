<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Entity\SupperClassExtension;
use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ProtectedPropertySupperclassTest extends BaseTestCaseORM
{
    public const SUPERCLASS = 'Blameable\\Fixture\\Entity\\SupperClassExtension';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber($translatableListener);
        $blameableListener = new BlameableListener();
        $blameableListener->setUserValue('testuser');
        $evm->addEventSubscriber($blameableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testProtectedProperty()
    {
        $test = new SupperClassExtension();
        $test->setName('name');
        $test->setTitle('title');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($test);
        $this->assertCount(0, $translations);

        $this->assertEquals('testuser', $test->getCreatedBy());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TRANSLATION,
            self::SUPERCLASS,
        ];
    }
}
