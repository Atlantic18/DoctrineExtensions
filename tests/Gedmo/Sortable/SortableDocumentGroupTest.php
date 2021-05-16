<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Sortable\Fixture\Document\Category;
use Sortable\Fixture\Document\Kid;
use Sortable\Fixture\Document\Post;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sortable behavior with SortableGroup
 *
 * @author http://github.com/vetalt
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableDocumentGroupTest extends BaseTestCaseMongoODM
{
    public const POST = 'Sortable\\Fixture\\Document\\Post';
    public const CATEGORY = 'Sortable\\Fixture\\Document\\Category';
    public const KID = 'Sortable\\Fixture\\Document\\Kid';
    public const KID_DATE1 = '1999-12-31';
    public const KID_DATE2 = '2000-01-01';

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    /**
     * Insert 2 categories, 6 posts and 4 kids
     * 3 posts are linked to a category, and 3 to the other one
     * 2 kids have one date, 2 another one
     */
    private function populate()
    {
        $categories = [];
        for ($i = 0; $i < 2; ++$i) {
            $categories[$i] = new Category();
            $categories[$i]->setName('category'.$i);
            $this->dm->persist($categories[$i]);
        }

        for ($i = 0; $i < 6; ++$i) {
            $post = new Post();
            $post->setTitle('post'.$i);
            $post->setCategory($categories[($i % 2)]);
            $this->dm->persist($post);
        }

        $birthdates = [
            new \DateTime(self::KID_DATE1),
            new \DateTime(self::KID_DATE2),
            ];

        for ($i = 0; $i < 4; ++$i) {
            $kid = new Kid();
            $kid->setLastName('kid'.$i);
            $kid->setBirthdate($birthdates[($i % 2)]);
            $this->dm->persist($kid);
        }
        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * There should be 2 kids by position
     */
    public function testKidInitialPositions()
    {
        $repo = $this->dm->getRepository(self::KID);

        for ($i = 0; $i < 2; ++$i) {
            $kids = $repo->findBy(['position' => $i]);
            $this->assertCount(2, $kids);
        }
    }

    /**
     * Move the last kid in the first position
     */
    public function testKidMovePosition()
    {
        $repo = $this->dm->getRepository(self::KID);

        $kid = $repo->findOneBy(['lastname' => 'kid2']);
        $this->assertInstanceOf(self::KID, $kid);

        $kid->setPosition(0);
        $this->dm->flush();

        $kids = $repo->findBy(['birthdate' => new \DateTime(self::KID_DATE1)]);
        $this->assertCount(2, $kids);

        for ($i = 0; $i < 2; ++$i) {
            $expected = (1 == $i + 1) ? $i + 1 : 0;
            $this->assertEquals($expected, $kids[$i]->getPosition());
        }
    }

    /**
     * There should be 2 posts by position
     */
    public function testPostsInitialPositions()
    {
        $repo = $this->dm->getRepository(self::POST);

        for ($i = 0; $i < 3; ++$i) {
            $posts = $repo->findBy(['position' => $i]);
            $this->assertCount(2, $posts);
        }
    }

    /**
     * Move the last inserted post in first position and check
     */
    public function testPostsMovePosition()
    {
        $repo_category = $this->dm->getRepository(self::CATEGORY);
        $repo_post = $this->dm->getRepository(self::POST);

        $category = $repo_category->findOneBy(['name' => 'category1']);
        $this->assertInstanceOf(self::CATEGORY, $category);

        $post = $repo_post->findOneBy([
            'position' => 2,
            'category.id' => $category->getId(),
        ]);
        $this->assertInstanceOf(self::POST, $post);

        $post->setPosition(0);

        $this->dm->flush();

        $posts = $repo_post->findBy([
            'category.id' => $category->getId(),
        ]);
        $this->assertCount(3, $posts);

        for ($i = 0; $i < 3; ++$i) {
            $expected = ($i + 1 < 3) ? $i + 1 : 0;
            $this->assertEquals($expected, $posts[$i]->getPosition());
        }
    }

    /**
     * Delete the 2nd post linked to a Category and check
     */
    public function testPostsDeletePosition()
    {
        $repo_category = $this->dm->getRepository(self::CATEGORY);
        $repo_post = $this->dm->getRepository(self::POST);

        $category = $repo_category->findOneBy(['name' => 'category1']);
        $this->assertInstanceOf(self::CATEGORY, $category);

        $post = $repo_post->findOneBy([
            'position' => 1,
            'category.id' => $category->getId(),
        ]);
        $this->assertInstanceOf(self::POST, $post);

        $this->dm->remove($post);
        $this->dm->flush();

        $posts = $repo_post->findBy([
            'category.id' => $category->getId(),
        ]);
        $this->assertCount(2, $posts);

        for ($i = 0; $i < 2; ++$i) {
            $this->assertEquals($i, $posts[$i]->getPosition());
        }
    }
}
