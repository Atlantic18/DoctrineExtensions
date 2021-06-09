<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue1177\Article;
use Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue1177Test extends BaseTestCaseORM
{
    public const ARTICLE = 'Sluggable\\Fixture\\Issue1177\\Article';

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
    public function shouldTryPreferedSlugFirst()
    {
        $article = new Article();
        $article->setTitle('the title with number 1');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals('the-title-with-number-1', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        // the slug was 'the-title-with-number-2' before the fix here
        // despite the fact that there is no entity with slug 'the-title-with-number'
        $this->assertEquals('the-title-with-number', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->assertEquals('the-title-with-number-2', $article->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
    }
}
