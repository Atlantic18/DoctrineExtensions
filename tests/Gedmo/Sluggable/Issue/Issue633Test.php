<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue633\Article;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Derek Clapham <derek.clapham@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue633Test extends BaseTestCaseORM
{
    public const TARGET = 'Sluggable\\Fixture\\Issue633\\Article';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleUniqueBasedSlug()
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test2->getSlug());

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }

    /**
     * @test
     */
    public function handlePersistedSlugsForUniqueBased()
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-code', $test->getSlug());
        $this->assertEquals('unique-to-code', $test2->getSlug());
        $this->assertEquals('unique-to-code-1', $test3->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
