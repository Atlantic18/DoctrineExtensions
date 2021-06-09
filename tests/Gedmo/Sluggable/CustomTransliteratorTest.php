<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Article;
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
class CustomTransliteratorTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Sluggable\\Fixture\\Article';

    public function testStandardTransliteratorFailsOnChineseCharacters()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);
        $this->assertEquals('bei-jing-zh', $chinese->getSlug());
    }

    public function testCanUseCustomTransliterator()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new MySluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);
        $this->assertEquals('bei-jing', $chinese->getSlug());
    }

    private function populate()
    {
        $chinese = new Article();
        $chinese->setTitle('北京');
        $chinese->setCode('zh');
        $this->em->persist($chinese);
        $this->em->flush();
        $this->em->clear();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
    }
}

class MySluggableListener extends SluggableListener
{
    public function __construct()
    {
        $this->setTransliterator(['\Gedmo\Sluggable\Transliterator', 'transliterate']);
    }
}

class Transliterator
{
    public static function transliterate($text, $separator, $object)
    {
        return 'Bei Jing';
    }
}
