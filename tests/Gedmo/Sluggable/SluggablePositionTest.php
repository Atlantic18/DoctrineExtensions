<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Sluggable\Fixture\Position;
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
class SluggablePositionTest extends BaseTestCaseORM
{
    public const POSITION = 'Sluggable\\Fixture\\Position';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testPositionedSlugOrder()
    {
        $meta = $this->em->getClassMetadata(self::POSITION);
        $repo = $this->em->getRepository(self::POSITION);

        $object = $repo->find(1);
        $slug = $meta->getReflectionProperty('slug')->getValue($object);
        $this->assertEquals('code-other-title-prop', $slug);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::POSITION,
        ];
    }

    private function populate()
    {
        $meta = $this->em->getClassMetadata(self::POSITION);
        $object = new Position();
        $meta->getReflectionProperty('title')->setValue($object, 'title');
        $meta->getReflectionProperty('prop')->setValue($object, 'prop');
        $meta->getReflectionProperty('code')->setValue($object, 'code');
        $meta->getReflectionProperty('other')->setValue($object, 'other');

        $this->em->persist($object);
        $this->em->flush();
    }
}
