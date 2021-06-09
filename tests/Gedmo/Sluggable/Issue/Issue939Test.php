<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Issue939\Article;
use Sluggable\Fixture\Issue939\Category;
use Sluggable\Fixture\Issue939\SluggableListener as SluggableListenerIssue939;
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
class Issue939Test extends BaseTestCaseORM
{
    public const ARTICLE = 'Sluggable\\Fixture\\Issue939\\Article';
    public const CATEGORY = 'Sluggable\\Fixture\\Issue939\\Category';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListenerIssue939());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $category = new Category();
        $category->setTitle('Misc articles');
        $this->em->persist($category);

        $article = new Article();
        $article->setTitle('Is there water on the moon?');
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        $this->assertEquals('Is there water on the moon?', $article->getSlug());
        $this->assertEquals('misc-articles', $category->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::CATEGORY,
        ];
    }
}
