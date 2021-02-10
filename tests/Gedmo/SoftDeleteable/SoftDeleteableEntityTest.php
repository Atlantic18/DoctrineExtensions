<?php

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use SoftDeleteable\Fixture\Entity\Article;
use SoftDeleteable\Fixture\Entity\Child;
use SoftDeleteable\Fixture\Entity\Comment;
use SoftDeleteable\Fixture\Entity\MegaPage;
use SoftDeleteable\Fixture\Entity\Module;
use SoftDeleteable\Fixture\Entity\OtherArticle;
use SoftDeleteable\Fixture\Entity\OtherComment;
use SoftDeleteable\Fixture\Entity\User;
use SoftDeleteable\Fixture\Entity\UserNoHardDelete;
use Tool\BaseTestCaseORM;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Patrik Votoček <patrik@votocek.cz>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableEntityTest extends BaseTestCaseORM
{
    const ARTICLE_CLASS = 'SoftDeleteable\Fixture\Entity\Article';
    const COMMENT_CLASS = 'SoftDeleteable\Fixture\Entity\Comment';
    const PAGE_CLASS = 'SoftDeleteable\Fixture\Entity\Page';
    const MEGA_PAGE_CLASS = 'SoftDeleteable\Fixture\Entity\MegaPage';
    const MODULE_CLASS = 'SoftDeleteable\Fixture\Entity\Module';
    const OTHER_ARTICLE_CLASS = 'SoftDeleteable\Fixture\Entity\OtherArticle';
    const OTHER_COMMENT_CLASS = 'SoftDeleteable\Fixture\Entity\OtherComment';
    const USER_CLASS = 'SoftDeleteable\Fixture\Entity\User';
    const MAPPED_SUPERCLASS_CHILD_CLASS = 'SoftDeleteable\Fixture\Entity\Child';
    const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    const USER_NO_HARD_DELETE_CLASS = 'SoftDeleteable\Fixture\Entity\UserNoHardDelete';

    private $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em = $this->getMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    /**
     * @test
     */
    public function shouldBeAbleToHardDeleteSoftdeletedItems()
    {
        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $newUser->setUsername($username = 'test_user');

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user);
    }

    /**
     * @test
     */
    public function shouldSoftlyDeleteIfColumnNameDifferFromPropertyName()
    {
        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        $this->assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user, 'User should be filtered out');

        // now deactivate filter and attempt to hard delete
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNotNull($user, 'User should be fetched when filter is disabled');

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user, 'User is still available after hard delete');
    }

    public function testSoftDeleteable()
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        $this->assertNull($art->getDeletedAt());
        $this->assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        $this->assertNull($art);
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        $this->assertNull($comment);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        $this->assertTrue(is_object($art));
        $this->assertTrue(is_object($art->getDeletedAt()));
        $this->assertTrue($art->getDeletedAt() instanceof \DateTime);
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        $this->assertTrue(is_object($comment));
        $this->assertTrue(is_object($comment->getDeletedAt()));
        $this->assertTrue($comment->getDeletedAt() instanceof \DateTime);

        $this->em->createQuery('UPDATE '.self::ARTICLE_CLASS.' a SET a.deletedAt = NULL')->execute();

        $this->em->refresh($art);
        $this->em->refresh($comment);

        // Now we try with a DQL Delete query
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $dql = sprintf('DELETE FROM %s a WHERE a.%s = :%s',
            self::ARTICLE_CLASS, $field, $field);
        $query = $this->em->createQuery($dql);
        $query->setParameter($field, $value);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker'
        );

        $query->execute();

        $art = $repo->findOneBy([$field => $value]);
        $this->assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $art = $repo->findOneBy([$field => $value]);

        $this->assertTrue(is_object($art));
        $this->assertTrue(is_object($art->getDeletedAt()));
        $this->assertTrue($art->getDeletedAt() instanceof \DateTime);

        // Inheritance tree DELETE DQL
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $megaPageRepo = $this->em->getRepository(self::MEGA_PAGE_CLASS);
        $module = new Module();
        $module->setTitle('Module 1');
        $page = new MegaPage();
        $page->setTitle('Page 1');
        $page->addModule($module);
        $module->setPage($page);

        $this->em->persist($page);
        $this->em->persist($module);
        $this->em->flush();

        $dql = sprintf('DELETE FROM %s p',
            self::PAGE_CLASS);
        $query = $this->em->createQuery($dql);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker'
        );

        $query->execute();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);
        $this->assertNull($p);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);

        $this->assertTrue(is_object($p));
        $this->assertTrue(is_object($p->getDeletedAt()));
        $this->assertTrue($p->getDeletedAt() instanceof \DateTime);

        // Test of #301
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $otherArticleRepo = $this->em->getRepository(self::OTHER_ARTICLE_CLASS);
        $otherCommentRepo = $this->em->getRepository(self::OTHER_COMMENT_CLASS);
        $otherArt = new OtherArticle();
        $otherComment = new OtherComment();
        $otherArt->setTitle('Page 1');
        $otherComment->setComment('Comment');
        $otherArt->addComment($otherComment);
        $otherComment->setArticle($otherArt);

        $this->em->persist($otherArt);
        $this->em->persist($otherComment);
        $this->em->flush();

        $this->em->refresh($otherArt);
        $this->em->refresh($otherComment);

        $artId = $otherArt->getId();
        $commentId = $otherComment->getId();

        $this->em->remove($otherArt);
        $this->em->flush();

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        $this->assertNull($foundArt);
        $this->assertTrue(is_object($foundComment));
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);

        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        $this->assertTrue(is_object($foundArt));
        $this->assertTrue(is_object($foundArt->getDeletedAt()));
        $this->assertTrue($foundArt->getDeletedAt() instanceof \DateTime);
        $this->assertTrue(is_object($foundComment));
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);
    }

    public function testSoftDeleteableWithDateTimeInterface()
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        $this->assertNull($art->getDeletedAt());
        $this->assertNull($comment->getDeletedAt());

        $art->setDeletedAt(new \DateTime());
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        $this->assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        $this->assertIsObject($art);
        $this->assertIsObject($art->getDeletedAt());
        $this->assertInstanceOf('DateTimeInterface', $art->getDeletedAt());
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        $this->assertIsObject($comment);
        $this->assertNull($comment->getDeletedAt());

        $this->em->createQuery('UPDATE '.self::ARTICLE_CLASS.' a SET a.deletedAt = NULL')->execute();

        $this->em->refresh($art);
        $this->em->refresh($comment);

        // Now we try with a DQL Delete query
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $dql = sprintf('DELETE FROM %s a WHERE a.%s = :%s',
            self::ARTICLE_CLASS, $field, $field);
        $query = $this->em->createQuery($dql);
        $query->setParameter($field, $value);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker'
        );

        $query->execute();

        $art = $repo->findOneBy([$field => $value]);
        $this->assertNull($art);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $art = $repo->findOneBy([$field => $value]);

        $this->assertIsObject($art);
        $this->assertIsObject($art->getDeletedAt());
        $this->assertInstanceOf('DateTimeInterface', $art->getDeletedAt());

        // Inheritance tree DELETE DQL
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $megaPageRepo = $this->em->getRepository(self::MEGA_PAGE_CLASS);
        $module = new Module();
        $module->setTitle('Module 1');
        $page = new MegaPage();
        $page->setTitle('Page 1');
        $page->addModule($module);
        $module->setPage($page);

        $this->em->persist($page);
        $this->em->persist($module);
        $this->em->flush();

        $dql = sprintf('DELETE FROM %s p',
            self::PAGE_CLASS);
        $query = $this->em->createQuery($dql);
        $query->setHint(
            \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\SoftDeleteable\Query\TreeWalker\SoftDeleteableWalker'
        );

        $query->execute();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);
        $this->assertNull($p);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->em->clear();

        $p = $megaPageRepo->findOneBy(['title' => 'Page 1']);

        $this->assertIsObject($p);
        $this->assertIsObject($p->getDeletedAt());
        $this->assertInstanceOf('DateTimeInterface', $p->getDeletedAt());

        // Test of #301
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        $otherArticleRepo = $this->em->getRepository(self::OTHER_ARTICLE_CLASS);
        $otherCommentRepo = $this->em->getRepository(self::OTHER_COMMENT_CLASS);
        $otherArt = new OtherArticle();
        $otherComment = new OtherComment();
        $otherArt->setTitle('Page 1');
        $otherComment->setComment('Comment');
        $otherArt->addComment($otherComment);
        $otherComment->setArticle($otherArt);

        $this->em->persist($otherArt);
        $this->em->persist($otherComment);
        $this->em->flush();

        $this->em->refresh($otherArt);
        $this->em->refresh($otherComment);

        $artId = $otherArt->getId();
        $commentId = $otherComment->getId();

        $otherArt->setDeletedAt(new \DateTime());
        $this->em->flush();

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        $this->assertNull($foundArt);
        $this->assertIsObject($foundComment);
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);

        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        $this->assertIsObject($foundArt);
        $this->assertIsObject($foundArt->getDeletedAt());
        $this->assertInstanceOf('DateTimeInterface', $foundArt->getDeletedAt());
        $this->assertIsObject($foundComment);
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);
    }

    /**
     * Make sure that soft delete also works when configured on a mapped superclass
     */
    public function testMappedSuperclass()
    {
        $child = new Child();
        $child->setTitle('test title');

        $this->em->persist($child);
        $this->em->flush();

        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::MAPPED_SUPERCLASS_CHILD_CLASS);
        $this->assertNull($repo->findOneBy(['id' => $child->getId()]));

        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $this->assertNotNull($repo->findById($child->getId()));
    }

    public function testSoftDeleteableFilter()
    {
        $filter = $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $filter->disableForEntity(self::USER_CLASS);

        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        $this->assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNotNull($user->getDeletedAt());
        $this->assertInstanceOf('DateTime', $user->getDeletedAt());

        $filter->enableForEntity(self::USER_CLASS);

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user);
    }

    /**
     * @test
     */
    public function shouldFilterBeQueryCachedCorrectlyWhenToggledForEntity()
    {
        $cache = new ArrayCache();
        $this->em->getConfiguration()->setQueryCacheImpl($cache);

        $filter = $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $filter->disableForEntity(self::USER_CLASS);

        $repo = $this->em->getRepository(self::USER_CLASS);

        $newUser = new User();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(array('username' => $username));

        $this->assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $dql = 'SELECT u FROM '.self::USER_CLASS.' u WHERE u.username = :username';
        $q = $this->em->createQuery($dql)
                      ->setParameter('username', $username)
        ;
        $data = $q->getResult();
        $this->assertCount(1, $data);
        $user = $data[0];
        $this->assertNotNull($user->getDeletedAt());

        $filter->enableForEntity(self::USER_CLASS);

        // The result should be different even with the query cache enabled.
        $q = $this->em->createQuery($dql)
                      ->setParameter('username', $username)
        ;
        $data = $q->getResult();
        $this->assertCount(0, $data);
    }

    public function testPostSoftDeleteEventIsDispatched()
    {
        $subscriber = $this->getMockBuilder("Doctrine\Common\EventSubscriber")
            ->setMethods([
                'getSubscribedEvents',
                'preSoftDelete',
                'postSoftDelete',
            ])
            ->getMock();

        $subscriber->expects($this->once())
                   ->method('getSubscribedEvents')
                   ->will($this->returnValue([
                       SoftDeleteableListener::PRE_SOFT_DELETE,
                       SoftDeleteableListener::POST_SOFT_DELETE,
                   ]));

        $subscriber->expects($this->exactly(2))
                   ->method('preSoftDelete')
                   ->with($this->anything());

        $subscriber->expects($this->exactly(2))
                   ->method('postSoftDelete')
                   ->with($this->anything());

        $this->em->getEventManager()->addEventSubscriber($subscriber);

        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        $this->assertNull($art->getDeletedAt());
        $this->assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();
    }

    /**
     * @test
     */
    public function shouldNotDeleteIfColumnNameDifferFromPropertyName()
    {
        $repo = $this->em->getRepository(self::USER_NO_HARD_DELETE_CLASS);

        $newUser = new UserNoHardDelete();
        $username = 'test_user';
        $newUser->setUsername($username);

        $this->em->persist($newUser);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);

        $this->assertNull($user->getDeletedAt());

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user, 'User should be filtered out');

        // now deactivate filter and attempt to hard delete
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);
        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNotNull($user, 'User should be fetched when filter is disabled');

        $this->em->remove($user);
        $this->em->flush();

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNotNull($user, 'User is still available, hard delete done');
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE_CLASS,
            self::PAGE_CLASS,
            self::MEGA_PAGE_CLASS,
            self::MODULE_CLASS,
            self::COMMENT_CLASS,
            self::USER_CLASS,
            self::OTHER_ARTICLE_CLASS,
            self::OTHER_COMMENT_CLASS,
            self::MAPPED_SUPERCLASS_CHILD_CLASS,
            self::USER_NO_HARD_DELETE_CLASS,
        ];
    }
}
