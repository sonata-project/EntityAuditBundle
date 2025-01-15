<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EntityAuditBundle\Tests;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use SimpleThings\EntityAudit\ChangedEntity;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Revision;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\AnimalAudit;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\ArticleAudit;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\Cat;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\Dog;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\Fox;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\PetAudit;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\ProfileAudit;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\Rabbit;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\UserAudit;

final class CoreTest extends BaseTest
{
    protected $schemaEntities = [
        ArticleAudit::class,
        ArticleAudit::class,
        UserAudit::class,
        ProfileAudit::class,
        AnimalAudit::class,
        Fox::class,
        Rabbit::class,
        PetAudit::class,
        Cat::class,
        Dog::class,
    ];

    protected $auditedEntities = [
        ArticleAudit::class,
        UserAudit::class,
        ProfileAudit::class,
        AnimalAudit::class,
        Rabbit::class,
        Fox::class,
        Cat::class,
        Dog::class,
    ];

    public function testAuditable(): void
    {
        $user = new UserAudit('beberlei');
        $article = new ArticleAudit('test', 'yadda!', $user, 'text');
        $rabbit = new Rabbit('rabbit', 'white');
        $foxy = new Fox('foxy', 60);
        $doggy = new Dog('woof', 80);
        $cat = new Cat('pusheen', '#b5a89f');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->persist($article);
        $em->persist($rabbit);
        $em->persist($foxy);
        $em->persist($doggy);
        $em->persist($cat);
        $em->flush();

        static::assertCount(1, $em->getConnection()->fetchAllAssociative('SELECT id FROM revisions'));
        static::assertCount(1, $em->getConnection()->fetchAllAssociative('SELECT * FROM UserAudit_audit'));
        static::assertCount(1, $em->getConnection()->fetchAllAssociative('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(2, $em->getConnection()->fetchAllAssociative('SELECT * FROM AnimalAudit_audit'));

        $article->setText('oeruoa');
        $rabbit->setName('Rabbit');
        $rabbit->setColor('gray');
        $foxy->setName('Foxy');
        $foxy->setTailLength(55);

        $em->flush();

        static::assertCount(2, $em->getConnection()->fetchAllAssociative('SELECT id FROM revisions'));
        static::assertCount(2, $em->getConnection()->fetchAllAssociative('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(4, $em->getConnection()->fetchAllAssociative('SELECT * FROM AnimalAudit_audit'));

        $em->remove($user);
        $em->remove($article);
        $em->remove($rabbit);
        $em->remove($foxy);
        $em->flush();

        static::assertCount(3, $em->getConnection()->fetchAllAssociative('SELECT id FROM revisions'));
        static::assertCount(2, $em->getConnection()->fetchAllAssociative('SELECT * FROM UserAudit_audit'));
        static::assertCount(3, $em->getConnection()->fetchAllAssociative('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(6, $em->getConnection()->fetchAllAssociative('SELECT * FROM AnimalAudit_audit'));
    }

    public function testFind(): void
    {
        $user = new UserAudit('beberlei');
        $foxy = new Fox('foxy', 55);
        $cat = new Cat('pusheen', '#b5a89f');

        $em = $this->getEntityManager();

        $em->persist($cat);
        $em->persist($user);
        $em->persist($foxy);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userId = $user->getId();
        static::assertNotNull($userId);

        $auditUser = $reader->find(UserAudit::class, $userId, 1);

        static::assertInstanceOf(UserAudit::class, $auditUser, 'Audited User is also a User instance.');
        static::assertSame($user->getId(), $auditUser->getId(), 'Ids of audited user and real user should be the same.');
        static::assertSame($user->getName(), $auditUser->getName(), 'Name of audited user and real user should be the same.');
        static::assertFalse($em->contains($auditUser), 'Audited User should not be in the identity map.');
        static::assertNotSame($user, $auditUser, 'User and Audited User instances are not the same.');

        $foxyId = $foxy->getId();
        static::assertNotNull($foxyId);

        $auditFox = $reader->find(Fox::class, $foxyId, 1);

        static::assertInstanceOf(Fox::class, $auditFox, "Audited SINGLE_TABLE class keeps it's class.");
        static::assertSame($foxy->getId(), $auditFox->getId(), 'Ids of audited SINGLE_TABLE class and real SINGLE_TABLE class should be the same.');
        static::assertSame($foxy->getName(), $auditFox->getName(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        static::assertSame($foxy->getTailLength(), $auditFox->getTailLength(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        static::assertFalse($em->contains($auditFox), 'Audited SINGLE_TABLE inheritance class should not be in the identity map.');
        static::assertNotSame($foxy, $auditFox, 'Audited and new entities should not be the same object for SINGLE_TABLE inheritance.');

        $catId = $cat->getId();
        static::assertNotNull($catId);

        $auditCat = $reader->find(Cat::class, $catId, 1);

        static::assertInstanceOf(Cat::class, $auditCat, "Audited JOINED class keeps it's class.");
        static::assertSame($cat->getId(), $auditCat->getId(), 'Ids of audited JOINED class and real JOINED class should be the same.');
        static::assertSame($cat->getName(), $auditCat->getName(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        static::assertSame($cat->getColor(), $auditCat->getColor(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        static::assertFalse($em->contains($auditCat), 'Audited JOINED inheritance class should not be in the identity map.');
        static::assertNotSame($cat, $auditCat, 'Audited and new entities should not be the same object for JOINED inheritance.');
    }

    public function testFindNoRevisionFound(): void
    {
        $reader = $this->getAuditManager()->createAuditReader($this->getEntityManager());

        $this->expectException(NoRevisionFoundException::class);
        $this->expectExceptionMessage(\sprintf(
            'No revision of class "%s" (1) was found at revision 1 or before. The entity did not exist at the specified revision yet.',
            UserAudit::class
        ));

        $reader->find(UserAudit::class, 1, 1);
    }

    public function testFindNotAudited(): void
    {
        $reader = $this->getAuditManager()->createAuditReader($this->getEntityManager());

        $this->expectException(NotAuditedException::class);
        $this->expectExceptionMessage('Class "stdClass" is not audited.');

        $reader->find(\stdClass::class, 1, 1);
    }

    public function testFindRevisionHistory(): void
    {
        $user = new UserAudit('beberlei');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        $article = new ArticleAudit('test', 'yadda!', $user, 'text');

        $em->persist($article);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);
        $revisions = $reader->findRevisionHistory();

        static::assertCount(2, $revisions);
        static::assertContainsOnly(Revision::class, $revisions);

        static::assertSame('2', (string) $revisions[0]->getRev());
        static::assertInstanceOf(\DateTime::class, $revisions[0]->getTimestamp());
        static::assertSame('beberlei', $revisions[0]->getUsername());

        static::assertSame('1', (string) $revisions[1]->getRev());
        static::assertInstanceOf(\DateTime::class, $revisions[1]->getTimestamp());
        static::assertSame('beberlei', $revisions[1]->getUsername());
    }

    public function testFindEntitesChangedAtRevision(): void
    {
        $user = new UserAudit('beberlei');
        $article = new ArticleAudit('test', 'yadda!', $user, 'text');
        $foxy = new Fox('foxy', 50);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $em = $this->getEntityManager();

        $em->persist($dog);
        $em->persist($cat);
        $em->persist($foxy);
        $em->persist($rabbit);
        $em->persist($user);
        $em->persist($article);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(1);

        // duplicated entries means a bug with discriminators
        static::assertCount(6, $changedEntities);
        static::assertContainsOnly(ChangedEntity::class, $changedEntities);

        static::assertSame(ArticleAudit::class, $changedEntities[0]->getClassName());
        static::assertSame('INS', $changedEntities[0]->getRevisionType());
        static::assertArrayHasKey('id', $changedEntities[0]->getId());
        static::assertSame('1', (string) $changedEntities[0]->getId()['id']);
        static::assertInstanceOf(ArticleAudit::class, $changedEntities[0]->getEntity());

        static::assertSame(UserAudit::class, $changedEntities[1]->getClassName());
        static::assertSame('INS', $changedEntities[1]->getRevisionType());
        static::assertArrayHasKey('id', $changedEntities[1]->getId());
        static::assertSame('1', (string) $changedEntities[1]->getId()['id']);
        static::assertInstanceOf(UserAudit::class, $changedEntities[1]->getEntity());
    }

    public function testNotVersionedRelationFind(): void
    {
        $em = $this->getEntityManager();

        // Insert user without the manager to skip revision registering.
        $em->getConnection()->insert(
            $em->getClassMetadata(UserAudit::class)->getTableName(),
            [
                'id' => 1,
                'name' => 'beberlei',
            ]
        );

        $userAudit = $em->getRepository(UserAudit::class)->find(1);
        static::assertNotNull($userAudit);

        $article = new ArticleAudit(
            'test',
            'yadda!',
            $userAudit,
            'text'
        );

        $em->persist($article);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $articleAudit = $reader->find(ArticleAudit::class, 1, 1);
        static::assertNotNull($articleAudit);
        $articleAuditAuthor = $articleAudit->getAuthor();
        static::assertNotNull($articleAuditAuthor);
        static::assertSame('beberlei', $articleAuditAuthor->getName());
    }

    public function testNotVersionedReverseRelationFind(): void
    {
        $user = new UserAudit('beberlei');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        // Insert user without the manager to skip revision registering.
        $em->getConnection()->insert(
            $em->getClassMetadata(ProfileAudit::class)->getTableName(),
            [
                'id' => 1,
                'biography' => 'He is an amazing contributor!',
                'user_id' => 1,
            ]
        );

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userAudit = $reader->find(UserAudit::class, 1, 1);
        static::assertNotNull($userAudit);
        $userAuditProfile = $userAudit->getProfile();
        static::assertNotNull($userAuditProfile);
        static::assertSame('He is an amazing contributor!', $userAuditProfile->getBiography());
    }

    public function testFindRevisions(): void
    {
        $user = new UserAudit('beberlei');
        $foxy = new Fox('foxy', 30);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $em = $this->getEntityManager();

        $em->persist($dog);
        $em->persist($cat);
        $em->persist($user);
        $em->persist($foxy);
        $em->persist($rabbit);
        $em->flush();

        $foxy->setName('Foxy');
        $dog->setName('doge');
        $user->setName('beberlei2');
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userId = $user->getId();
        static::assertNotNull($userId);
        $revisions = $reader->findRevisions(UserAudit::class, $userId);

        static::assertCount(2, $revisions);
        static::assertContainsOnly(Revision::class, $revisions);

        static::assertSame('2', (string) $revisions[0]->getRev());
        static::assertInstanceOf(\DateTime::class, $revisions[0]->getTimestamp());
        static::assertSame('beberlei', $revisions[0]->getUsername());

        static::assertSame('1', (string) $revisions[1]->getRev());
        static::assertInstanceOf(\DateTime::class, $revisions[1]->getTimestamp());
        static::assertSame('beberlei', $revisions[1]->getUsername());

        // SINGLE_TABLE should have separate revision history
        $foxyId = $foxy->getId();
        static::assertNotNull($foxyId);
        static::assertCount(2, $reader->findRevisions(Fox::class, $foxyId));
        $rabbitId = $rabbit->getId();
        static::assertNotNull($rabbitId);
        static::assertCount(1, $reader->findRevisions(Rabbit::class, $rabbitId));
        // JOINED too
        $dogId = $dog->getId();
        static::assertNotNull($dogId);
        static::assertCount(2, $reader->findRevisions(Dog::class, $dogId));
        $catId = $cat->getId();
        static::assertNotNull($catId);
        static::assertCount(1, $reader->findRevisions(Cat::class, $catId));
    }

    public function testFindCurrentRevision(): void
    {
        $user = new UserAudit('Broncha');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        $user->setName('Rajesh');
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userId = $user->getId();
        static::assertNotNull($userId);

        $revision = $reader->getCurrentRevision(UserAudit::class, $userId);
        static::assertSame('2', (string) $revision);

        $user->setName('David');
        $em->flush();

        $revision = $reader->getCurrentRevision(UserAudit::class, $userId);
        static::assertSame('3', (string) $revision);
    }

    public function testGlobalIgnoreColumns(): void
    {
        $user = new UserAudit('welante');
        $article = new ArticleAudit('testcolumn', 'yadda!', $user, 'text');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->persist($article);
        $em->flush();

        $article->setText('testcolumn2');
        $em->persist($article);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $articleId = $article->getId();
        static::assertNotNull($articleId);

        $revision = $reader->getCurrentRevision(ArticleAudit::class, $articleId);
        static::assertSame('2', (string) $revision);

        $article->setIgnoreme('textnew');
        $em->persist($article);
        $em->flush();

        $revision = $reader->getCurrentRevision(ArticleAudit::class, $articleId);
        static::assertSame('2', (string) $revision);
    }

    public function testDeleteUnInitProxy(): void
    {
        $user = new UserAudit('beberlei');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        unset($user);
        $em->clear();

        $user = $em->getReference(UserAudit::class, 1);
        static::assertNotNull($user);
        $em->remove($user);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        static::assertCount(1, $changedEntities);
        static::assertContainsOnly(ChangedEntity::class, $changedEntities);
        static::assertSame(UserAudit::class, $changedEntities[0]->getClassName());
        static::assertSame('DEL', $changedEntities[0]->getRevisionType());
        static::assertArrayHasKey('id', $changedEntities[0]->getId());
        static::assertSame('1', (string) $changedEntities[0]->getId()['id']);
    }

    public function testUsernameResolvingIsDynamic(): void
    {
        $this->getAuditManager()->getConfiguration()->setUsernameCallable(
            static fn () => 'user: '.uniqid()
        );

        $user = new UserAudit('beberlei');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();

        $user->setName('b.eberlei');
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);
        $revisions = $reader->findRevisionHistory();

        static::assertCount(2, $revisions);

        $revision0 = $revisions[0];
        static::assertInstanceOf(Revision::class, $revision0);
        $revision1 = $revisions[1];
        static::assertInstanceOf(Revision::class, $revision1);

        static::assertStringStartsWith('user: ', $revision0->getUsername() ?? '');
        static::assertStringStartsWith('user: ', $revision1->getUsername() ?? '');

        static::assertNotSame($revision0->getUsername(), $revision1->getUsername());
    }

    public function testRevisionForeignKeys(): void
    {
        $em = $this->getEntityManager();

        $isSqlitePlatform = $em->getConnection()->getDatabasePlatform() instanceof SqlitePlatform;
        $updateForeignKeysConfig = false;

        if ($isSqlitePlatform) {
            $foreignKeysConfig = $em->getConnection()->executeQuery('PRAGMA foreign_keys;')->fetchOne();
            $updateForeignKeysConfig = '0' === $foreignKeysConfig || 0 === $foreignKeysConfig;

            if ($updateForeignKeysConfig) {
                // Enable the "foreign_keys" pragma.
                $em->getConnection()->executeQuery('PRAGMA foreign_keys = ON;');
            }
        }

        $user = new UserAudit('phansys');

        $em->persist($user);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userId = $user->getId();
        static::assertNotNull($userId);

        $revisions = $reader->findRevisions(UserAudit::class, $userId);

        static::assertCount(1, $revisions);

        $revision = $reader->getCurrentRevision(UserAudit::class, $userId);
        static::assertSame('1', (string) $revision);

        $revisionsTableName = $this->getAuditManager()->getConfiguration()->getRevisionTableName();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('#SQLSTATE\[[\d]+\]: #');

        try {
            $em->getConnection()->delete($revisionsTableName, ['id' => $revision]);
        } finally {
            if ($updateForeignKeysConfig) {
                // Restore the original value for the "foreign_keys" pragma.
                $em->getConnection()->executeQuery('PRAGMA foreign_keys = OFF;');
            }
        }
    }
}
