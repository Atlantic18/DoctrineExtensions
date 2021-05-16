<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Sortable\Fixture\Document\Article;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sortable behavior
 *
 * @author http://github.com/vetalt
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = 'Sortable\\Fixture\\Document\\Article';

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    private function populate()
    {
        for ($i = 0; $i <= 4; ++$i) {
            $article = new Article();
            $article->setTitle('article'.$i);
            $this->dm->persist($article);
        }
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testInitialPositions()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        for ($i = 0; $i <= 4; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            $this->assertEquals('article'.$i, $article->getTitle());
        }
    }

    public function testMovePositions()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 4]);
        $article->setPosition(0);
        $this->dm->flush();

        for ($i = 1; $i <= 4; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            $this->assertEquals('article'.($i - 1), $article->getTitle());
        }
    }

    public function testMoveLastPositions()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 0]);
        $article->setPosition(-1);
        $this->dm->flush();

        for ($i = 0; $i <= 3; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            $this->assertEquals('article'.($i + 1), $article->getTitle());
        }
        $article = $repo->findOneBy(['position' => 4]);
        $this->assertEquals('article0', $article->getTitle());
    }

    public function testDeletePositions()
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 0]);
        $this->dm->remove($article);
        $this->dm->flush();

        for ($i = 0; $i <= 3; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            $this->assertEquals('article'.($i + 1), $article->getTitle());
        }
    }
}
