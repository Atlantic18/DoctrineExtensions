<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\StringIdentifier;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableIdentifierTest extends BaseTestCaseORM
{
    public const FIXTURE = 'Translatable\\Fixture\\StringIdentifier';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $testObjectId;
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $conn = [
                    'driver' => 'pdo_mysql',
                    'host' => '127.0.0.1',
                    'dbname' => 'test',
                    'user' => 'root',
                    'password' => 'nimda',
        ];
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleStringIdentifier()
    {
        $object = new StringIdentifier();
        $object->setTitle('title in en');
        $object->setUid(md5(self::FIXTURE.time()));

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();
        $this->testObjectId = $object->getUid();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $object = $this->em->find(self::FIXTURE, $this->testObjectId);

        $translations = $repo->findTranslations($object);
        $this->assertCount(0, $translations);

        $object = $this->em->find(self::FIXTURE, $this->testObjectId);
        $object->setTitle('title in de');
        $object->setTranslatableLocale('de_de');

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);

        // test the entity load by translated title
        $object = $repo->findObjectByTranslatedField(
            'title',
            'title in de',
            self::FIXTURE
        );

        $this->assertEquals($this->testObjectId, $object->getUid());

        $translations = $repo->findTranslations($object);
        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        // dql test object hydration
        $q = $this->em
            ->createQuery('SELECT si FROM '.self::FIXTURE.' si WHERE si.uid = :id')
            ->setParameter('id', $this->testObjectId)
            ->useResultCache(false)
        ;
        $data = $q->getResult();
        $this->assertCount(1, $data);
        $object = $data[0];
        $this->assertEquals('title in en', $object->getTitle());

        $this->em->clear(); // based on 2.3.0 it caches in identity map
        $this->translatableListener->setTranslatableLocale('de_de');
        $data = $q->getResult();
        $this->assertCount(1, $data);
        $object = $data[0];
        $this->assertEquals('title in de', $object->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::FIXTURE,
            self::TRANSLATION,
        ];
    }
}
