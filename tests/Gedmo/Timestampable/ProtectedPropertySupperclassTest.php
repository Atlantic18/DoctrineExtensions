<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Timestampable\Fixture\SupperClassExtension;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ProtectedPropertySupperclassTest extends BaseTestCaseORM
{
    public const SUPERCLASS = 'Timestampable\\Fixture\\SupperClassExtension';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber($translatableListener);
        $evm->addEventSubscriber(new TimestampableListener());

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

        $this->assertNotNull($test->getCreatedAt());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TRANSLATION,
            self::SUPERCLASS,
        ];
    }
}
