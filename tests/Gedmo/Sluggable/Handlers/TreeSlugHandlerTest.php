<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Tree\TreeListener;
use Sluggable\Fixture\Handler\TreeSlug;
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
class TreeSlugHandlerTest extends BaseTestCaseORM
{
    public const TARGET = 'Sluggable\\Fixture\\Handler\\TreeSlug';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $food = $repo->findOneBy(['title' => 'Food']);
        $this->assertEquals('food', $food->getSlug());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $this->assertEquals('food/fruits', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $this->assertEquals('food/fruits/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        $this->assertEquals('food/fruits/citrons', $citrons->getSlug());

        $apple = $repo->findOneBy(['title' => 'Apple']);
        $this->assertEquals('food/fruits/apple', $apple->getSlug());

        $kiwi = $repo->findOneBy(['title' => 'Kiwi']);
        $this->assertEquals('food/fruits/kiwi', $kiwi->getSlug());

        $banana = $repo->findOneBy(['title' => 'Banana']);
        $this->assertEquals('food/fruits/banana', $banana->getSlug());
    }

    public function testSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');

        $this->em->persist($fruits);
        $this->em->flush();

        $this->assertEquals('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $this->assertEquals('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        $this->assertEquals('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        $this->assertEquals('foodissimo', $food->getSlug());
        $this->assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    public function testMoreSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');
        $milk = $repo->findOneBy(['title' => 'Milk']);
        $repo->persistAsFirstChildOf($fruits, $milk);
        $this->em->flush();

        $this->assertEquals('food/milk/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $this->assertEquals('food/milk/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        $this->assertEquals('food/milk/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        $this->assertEquals('foodissimo', $food->getSlug());
        $this->assertEquals('foodissimo/milk/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/milk/fructis/citrons', $citrons->getSlug());

        $repo->persistAsFirstChildOf($fruits, $food);
        $this->em->flush();

        $this->assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::TARGET);

        $food = new TreeSlug();
        $food->setTitle('Food');

        $fruits = new TreeSlug();
        $fruits->setTitle('Fruits');

        $vegitables = new TreeSlug();
        $vegitables->setTitle('Vegitables');

        $milk = new TreeSlug();
        $milk->setTitle('Milk');

        $meat = new TreeSlug();
        $meat->setTitle('Meat');

        $oranges = new TreeSlug();
        $oranges->setTitle('Oranges');

        $citrons = new TreeSlug();
        $citrons->setTitle('Citrons');

        $apple = new TreeSlug();
        $apple->setTitle('Apple');

        $kiwi = new TreeSlug();
        $kiwi->setTitle('Kiwi');

        $banana = new TreeSlug();
        $banana->setTitle('Banana');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits)
            ->persistAsFirstChildOf($apple, $fruits)
            ->persistAsPrevSiblingOf($kiwi, $apple)
            ->persistAsNextSiblingOf($banana, $apple);

        $this->em->flush();
    }
}
