<?php

namespace Gedmo\Translator;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Proxy\Proxy;
use Tool\BaseTestCaseORM;
use Translator\Fixture\Person;
use Translator\Fixture\PersonCustom;

/**
 * These are tests for translatable behavior
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableTest extends BaseTestCaseORM
{
    public const PERSON = 'Translator\\Fixture\\Person';
    public const PERSON_CUSTOM_PROXY = 'Translator\\Fixture\\PersonCustom';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->getMockSqliteEntityManager($evm);
    }

    public function testTranslatable()
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        // retrieve record (translations would be fetched later - by demand)
        $person = $this->em->getRepository(self::PERSON)->findOneBy(['name' => 'Jen']);

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        $person->translate('es_ES')->setName('Amigo');

        $this->em->flush();

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('Amigo', $person->translate('es_ES')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
    }

    /**
     * @test
     */
    public function shouldTranslateRelation()
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru')->setDescription('multilingual description');

        $parent = new Person();
        $parent->setName('Jen');
        $parent->translate('ru')->setName('Женя starshai');
        $parent->translate('fr')->setName('zenia');
        $parent->setDescription('description');
        $parent->translate('ru')->setDescription('multilingual description');

        $person->setParent($parent);
        $this->em->persist($person);
        $this->em->persist($parent);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(self::PERSON)->findOneBy(['name' => 'Jen']);
        $this->assertSame('Женя', $person->translate('ru')->getName());
        $parent = $person->getParent();
        $this->assertTrue($parent instanceof Proxy);
        $this->assertSame('Женя starshai', $parent->translate('ru')->getName());
        $this->assertSame('zenia', $parent->translate('fr')->getName());
    }

    /**
     * @test
     */
    public function shouldHandleDomainObjectProxy()
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $personProxy = $this->em->getReference(self::PERSON, ['id' => 1]);
        $this->assertTrue($personProxy instanceof Proxy);
        $name = $personProxy->translate('ru_RU')->getName();
        $this->assertSame('Женя', $name);
    }

    public function testTranslatableProxyWithUpperCaseProperty()
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->name = 'Женя';
        $person->setLastName('Abramowicz');
        $person->translate('ru_RU')->setLastName('Абрамович');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $personProxy = $this->em->getReference(self::PERSON, ['id' => 1]);
        $this->assertTrue($personProxy instanceof Proxy);
        $name = $personProxy->translate('ru_RU')->getName();
        $this->assertSame('Женя', $name);
        $lastName = $personProxy->translate('ru_RU')->getLastName();
        $this->assertSame('Абрамович', $lastName);
    }

    public function testTranslatableWithMagicProperties()
    {
        $person = new Person();
        $person->translate('en')->setName('Jen');
        $person->translate('ru_RU')->name = 'Женя';
        $person->translate('ru_RU')->description = 'multilingual description';

        $this->assertSame('Jen', $person->name);
        $this->assertSame('Jen', $person->translate()->name);
        $this->assertSame('Женя', $person->translate('ru_RU')->name);
        $this->assertSame('multilingual description', $person->translate('ru_RU')->description);
        $this->assertSame('multilingual description', $person->description);
    }

    public function testTranslatableWithCustomProxy()
    {
        $person = new PersonCustom();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        // retrieve record (translations would be fetched later - by demand)
        $person = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)->findOneBy(['name' => 'Jen']);

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        $this->assertSame('multilingual description', $person->getDescription());

        $person->translate('es_ES')->setName('Amigo');

        $this->em->flush();

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('Amigo', $person->translate('es_ES')->getName());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON, self::PERSON.'Translation',
            self::PERSON_CUSTOM_PROXY, self::PERSON_CUSTOM_PROXY.'Translation',
        ];
    }
}
