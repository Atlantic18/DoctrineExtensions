<?php

namespace Gedmo\SoftDeleteable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use SoftDeleteable\Fixture\Entity\Article;
use SoftDeleteable\Fixture\Entity\Comment;
use SoftDeleteable\Fixture\Entity\UserDateTimeImmutable;
use SoftDeleteable\Fixture\Entity\MegaPage;
use SoftDeleteable\Fixture\Entity\Module;
use SoftDeleteable\Fixture\Entity\OtherArticle;
use SoftDeleteable\Fixture\Entity\OtherComment;

/**
 * These are tests for SoftDeleteable behavior
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @author Javier Spagnoletti <phansys@gmail.com>
 */
final class SoftDeleteableEntityDateTimeImmutableTest extends BaseTestCaseORM
{
    const ARTICLE_CLASS = 'SoftDeleteable\Fixture\Entity\Article';
    const COMMENT_CLASS = 'SoftDeleteable\Fixture\Entity\Comment';
    const PAGE_CLASS = 'SoftDeleteable\Fixture\Entity\Page';
    const MEGA_PAGE_CLASS = 'SoftDeleteable\Fixture\Entity\MegaPage';
    const MODULE_CLASS = 'SoftDeleteable\Fixture\Entity\Module';
    const OTHER_ARTICLE_CLASS = 'SoftDeleteable\Fixture\Entity\OtherArticle';
    const OTHER_COMMENT_CLASS = 'SoftDeleteable\Fixture\Entity\OtherComment';
    const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';
    const USER_DATETIME_IMMUTABLE_CLASS = 'SoftDeleteable\Fixture\Entity\UserDateTimeImmutable';

    private $softDeleteableListener;

    protected function setUp(): void
    {
        if (!Type::hasType('datetime_immutable')) {
            $this->markTestSkipped('This test requires "date*_immutable" types to be defined, which are included with "doctrine/dbal:^2.6"');
        }
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getMockAnnotatedConfig();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em = $this->getMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
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

        $art->setDeletedAt(new \DateTimeImmutable());
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

        $otherArt->setDeletedAt(new \DateTimeImmutable());
        $this->em->flush();

        $foundArt = $otherArticleRepo->findOneBy(['id' => $artId]);
        $foundComment = $otherCommentRepo->findOneBy(['id' => $commentId]);

        $this->assertNull($foundArt);
        $this->assertIsObject($foundComment);
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);

        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $foundArt = $otherArticleRepo->findOneById($artId);
        $foundComment = $otherCommentRepo->findOneById($commentId);

        $this->assertIsObject($foundArt);
        $this->assertIsObject($foundArt->getDeletedAt());
        $this->assertInstanceOf('DateTimeInterface', $foundArt->getDeletedAt());
        $this->assertIsObject($foundComment);
        $this->assertInstanceOf(self::OTHER_COMMENT_CLASS, $foundComment);
    }

    public function testSoftDeleteableDateTimeImmutableFilter()
    {
        $filter = $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
        $filter->disableForEntity(self::USER_DATETIME_IMMUTABLE_CLASS);

        $repo = $this->em->getRepository(self::USER_DATETIME_IMMUTABLE_CLASS);

        $newUser = new UserDateTimeImmutable();
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
        $this->assertInstanceOf('DateTimeImmutable', $user->getDeletedAt());

        $filter->enableForEntity(self::USER_DATETIME_IMMUTABLE_CLASS);

        $user = $repo->findOneBy(['username' => $username]);
        $this->assertNull($user);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE_CLASS,
            self::PAGE_CLASS,
            self::MEGA_PAGE_CLASS,
            self::MODULE_CLASS,
            self::COMMENT_CLASS,
            self::OTHER_ARTICLE_CLASS,
            self::OTHER_COMMENT_CLASS,
            self::USER_DATETIME_IMMUTABLE_CLASS,
        ];
    }
}
