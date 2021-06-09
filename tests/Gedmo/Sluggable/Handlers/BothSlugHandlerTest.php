<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Sluggable\Fixture\Handler\People\Occupation;
use Sluggable\Fixture\Handler\People\Person;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BothSlugHandlerTest extends BaseTestCaseORM
{
    public const OCCUPATION = 'Sluggable\\Fixture\\Handler\\People\\Occupation';
    public const PERSON = 'Sluggable\\Fixture\\Handler\\People\\Person';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::PERSON);

        $herzult = $repo->findOneBy(['name' => 'Herzult']);
        $this->assertEquals('web/developer/php/herzult', $herzult->getSlug());

        $gedi = $repo->findOneBy(['name' => 'Gedi']);
        $this->assertEquals('web/developer/gedi', $gedi->getSlug());

        $hurty = $repo->findOneBy(['name' => 'Hurty']);
        $this->assertEquals('singer/hurty', $hurty->getSlug());
    }

    public function testSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::PERSON);

        $gedi = $repo->findOneBy(['name' => 'Gedi']);
        $gedi->setName('Upd Gedi');
        $this->em->persist($gedi);
        $this->em->flush();

        $this->assertEquals('web/developer/upd-gedi', $gedi->getSlug());

        $artist = $this->em->getRepository(self::OCCUPATION)->findOneBy(['title' => 'Singer']);
        $artist->setTitle('Artist');

        $this->em->persist($artist);
        $this->em->flush();

        $gedi->setOccupation($artist);
        $this->em->persist($gedi);
        $this->em->flush();

        $this->assertEquals('artist/upd-gedi', $gedi->getSlug());

        $hurty = $repo->findOneBy(['name' => 'Hurty']);
        $this->assertEquals('artist/hurty', $hurty->getSlug());
    }

    public function test1093()
    {
        $this->populate();
        $personRepo = $this->em->getRepository(self::PERSON);
        $occupationRepo = $this->em->getRepository(self::OCCUPATION);

        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        $this->assertEquals('web/developer/php/herzult', $herzult->getSlug());

        $developer = $occupationRepo->findOneBy(['title' => 'Developer']);
        $developer->setTitle('Enthusiast');

        $this->em->persist($developer);
        $this->em->flush();

        // Works (but is not updated in the actual DB)
        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        $this->assertEquals('web/enthusiast/php/herzult', $herzult->getSlug());

        $this->em->clear();

        // Does not work.
        $herzult = $personRepo->findOneBy(['name' => 'Herzult']);
        $this->assertEquals('web/enthusiast/php/herzult', $herzult->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::OCCUPATION,
            self::PERSON,
        ];
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::OCCUPATION);

        $web = new Occupation();
        $web->setTitle('Web');

        $developer = new Occupation();
        $developer->setTitle('Developer');

        $designer = new Occupation();
        $designer->setTitle('Designer');

        $php = new Occupation();
        $php->setTitle('PHP');

        $singer = new Occupation();
        $singer->setTitle('Singer');

        $rock = new Occupation();
        $rock->setTitle('Rock');

        // Singer
        // > Hurty
        // -- Rock
        // Web
        // -- Designer
        // -- Developer
        // -- -- PHP
        // -- -- > Herzult
        // -- > Gedi
        $repo
            ->persistAsFirstChild($web)
            ->persistAsFirstChild($singer)
            ->persistAsFirstChildOf($developer, $web)
            ->persistAsFirstChildOf($designer, $web)
            ->persistAsLastChildOf($php, $developer)
            ->persistAsLastChildOf($rock, $singer)
        ;

        $herzult = new Person();
        $herzult->setName('Herzult');
        $herzult->setOccupation($php);
        $this->em->persist($herzult);

        $gedi = new Person();
        $gedi->setName('Gedi');
        $gedi->setOccupation($developer);
        $this->em->persist($gedi);

        $hurty = new Person();
        $hurty->setName('Hurty');
        $hurty->setOccupation($singer);
        $this->em->persist($hurty);

        $this->em->flush();
    }
}
