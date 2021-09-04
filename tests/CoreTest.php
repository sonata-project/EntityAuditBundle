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

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\DBAL\Exception\DriverException;
use SimpleThings\EntityAudit\ChangedEntity;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Revision;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\AnimalAudit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Cat;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Dog;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Fox;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\PetAudit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\ProfileAudit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Rabbit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit;

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

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->persist($rabbit);
        $this->em->persist($foxy);
        $this->em->persist($doggy);
        $this->em->persist($cat);
        $this->em->flush();

        static::assertCount(1, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        static::assertCount(1, $this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit'));
        static::assertCount(1, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));

        $article->setText('oeruoa');
        $rabbit->setName('Rabbit');
        $rabbit->setColor('gray');
        $foxy->setName('Foxy');
        $foxy->setTailLength(55);

        $this->em->flush();

        static::assertCount(2, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        static::assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(4, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));

        $this->em->remove($user);
        $this->em->remove($article);
        $this->em->remove($rabbit);
        $this->em->remove($foxy);
        $this->em->flush();

        static::assertCount(3, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        static::assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit'));
        static::assertCount(3, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        static::assertCount(6, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));
    }

    public function testFind(): void
    {
        $user = new UserAudit('beberlei');
        $foxy = new Fox('foxy', 55);
        $cat = new Cat('pusheen', '#b5a89f');

        $this->em->persist($cat);
        $this->em->persist($user);
        $this->em->persist($foxy);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $auditUser = $reader->find(\get_class($user), $user->getId(), 1);

        static::assertInstanceOf(\get_class($user), $auditUser, 'Audited User is also a User instance.');
        static::assertSame($user->getId(), $auditUser->getId(), 'Ids of audited user and real user should be the same.');
        static::assertSame($user->getName(), $auditUser->getName(), 'Name of audited user and real user should be the same.');
        static::assertFalse($this->em->contains($auditUser), 'Audited User should not be in the identity map.');
        static::assertNotSame($user, $auditUser, 'User and Audited User instances are not the same.');

        $auditFox = $reader->find(\get_class($foxy), $foxy->getId(), 1);

        static::assertInstanceOf(\get_class($foxy), $auditFox, "Audited SINGLE_TABLE class keeps it's class.");
        static::assertSame($foxy->getId(), $auditFox->getId(), 'Ids of audited SINGLE_TABLE class and real SINGLE_TABLE class should be the same.');
        static::assertSame($foxy->getName(), $auditFox->getName(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        static::assertSame($foxy->getTailLength(), $auditFox->getTailLength(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        static::assertFalse($this->em->contains($auditFox), 'Audited SINGLE_TABLE inheritance class should not be in the identity map.');
        static::assertNotSame($this, $auditFox, 'Audited and new entities should not be the same object for SINGLE_TABLE inheritance.');

        $auditCat = $reader->find(\get_class($cat), $cat->getId(), 1);

        static::assertInstanceOf(\get_class($cat), $auditCat, "Audited JOINED class keeps it's class.");
        static::assertSame($cat->getId(), $auditCat->getId(), 'Ids of audited JOINED class and real JOINED class should be the same.');
        static::assertSame($cat->getName(), $auditCat->getName(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        static::assertSame($cat->getColor(), $auditCat->getColor(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        static::assertFalse($this->em->contains($auditCat), 'Audited JOINED inheritance class should not be in the identity map.');
        static::assertNotSame($this, $auditCat, 'Audited and new entities should not be the same object for JOINED inheritance.');
    }

    public function testFindNoRevisionFound(): void
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->expectException(NoRevisionFoundException::class);
        $this->expectExceptionMessage(sprintf(
            'No revision of class "%s" (1) was found at revision 1 or before. The entity did not exist at the specified revision yet.',
            UserAudit::class
        ));

        $reader->find(UserAudit::class, 1, 1);
    }

    public function testFindNotAudited(): void
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->expectException(NotAuditedException::class);
        $this->expectExceptionMessage('Class "stdClass" is not audited.');

        $reader->find('stdClass', 1, 1);
    }

    public function testFindRevisionHistory(): void
    {
        $user = new UserAudit('beberlei');

        $this->em->persist($user);
        $this->em->flush();

        $article = new ArticleAudit('test', 'yadda!', $user, 'text');

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $revisions = $reader->findRevisionHistory();

        static::assertCount(2, $revisions);
        static::assertContainsOnly(Revision::class, $revisions);

        static::assertSame('2', (string) $revisions[0]->getRev());
        static::assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        static::assertSame('beberlei', $revisions[0]->getUsername());

        static::assertSame('1', (string) $revisions[1]->getRev());
        static::assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
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

        $this->em->persist($dog);
        $this->em->persist($cat);
        $this->em->persist($foxy);
        $this->em->persist($rabbit);
        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(1);

        //duplicated entries means a bug with discriminators
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
        // Insert user without the manager to skip revision registering.
        $this->em->getConnection()->insert(
            $this->em->getClassMetadata(UserAudit::class)->getTableName(),
            [
                'id' => 1,
                'name' => 'beberlei',
            ]
        );

        $article = new ArticleAudit(
            'test',
            'yadda!',
            $this->em->getRepository(UserAudit::class)->find(1),
            'text'
        );

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        static::assertSame('beberlei', $reader->find(\get_class($article), 1, 1)->getAuthor()->getName());
    }

    public function testNotVersionedReverseRelationFind(): void
    {
        $user = new UserAudit('beberlei');

        $this->em->persist($user);
        $this->em->flush();

        // Insert user without the manager to skip revision registering.
        $this->em->getConnection()->insert(
            $this->em->getClassMetadata(ProfileAudit::class)->getTableName(),
            [
                'id' => 1,
                'biography' => 'He is an amazing contributor!',
                'user_id' => 1,
            ]
        );

        $reader = $this->auditManager->createAuditReader($this->em);

        static::assertSame('He is an amazing contributor!', $reader->find(\get_class($user), 1, 1)->getProfile()->getBiography());
    }

    public function testFindRevisions(): void
    {
        $user = new UserAudit('beberlei');
        $foxy = new Fox('foxy', 30);
        $rabbit = new Rabbit('rabbit', 'white');
        $cat = new Cat('pusheen', '#b5a89f');
        $dog = new Dog('doggy', 80);

        $this->em->persist($dog);
        $this->em->persist($cat);
        $this->em->persist($user);
        $this->em->persist($foxy);
        $this->em->persist($rabbit);
        $this->em->flush();

        $foxy->setName('Foxy');
        $dog->setName('doge');
        $user->setName('beberlei2');
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $revisions = $reader->findRevisions(\get_class($user), $user->getId());

        static::assertCount(2, $revisions);
        static::assertContainsOnly(Revision::class, $revisions);

        static::assertSame('2', (string) $revisions[0]->getRev());
        static::assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        static::assertSame('beberlei', $revisions[0]->getUsername());

        static::assertSame('1', (string) $revisions[1]->getRev());
        static::assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        static::assertSame('beberlei', $revisions[1]->getUsername());

        //SINGLE_TABLE should have separate revision history
        static::assertCount(2, $reader->findRevisions(\get_class($foxy), $foxy->getId()));
        static::assertCount(1, $reader->findRevisions(\get_class($rabbit), $rabbit->getId()));
        //JOINED too
        static::assertCount(2, $reader->findRevisions(\get_class($dog), $dog->getId()));
        static::assertCount(1, $reader->findRevisions(\get_class($cat), $cat->getId()));
    }

    public function testFindCurrentRevision(): void
    {
        $user = new UserAudit('Broncha');

        $this->em->persist($user);
        $this->em->flush();

        $user->setName('Rajesh');
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revision = $reader->getCurrentRevision(\get_class($user), $user->getId());
        static::assertSame('2', (string) $revision);

        $user->setName('David');
        $this->em->flush();

        $revision = $reader->getCurrentRevision(\get_class($user), $user->getId());
        static::assertSame('3', (string) $revision);
    }

    public function testGlobalIgnoreColumns(): void
    {
        $user = new UserAudit('welante');
        $article = new ArticleAudit('testcolumn', 'yadda!', $user, 'text');

        $this->em->persist($user);
        $this->em->persist($article);
        $this->em->flush();

        $article->setText('testcolumn2');
        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revision = $reader->getCurrentRevision(\get_class($article), $article->getId());
        static::assertSame('2', (string) $revision);

        $article->setIgnoreme('textnew');
        $this->em->persist($article);
        $this->em->flush();

        $revision = $reader->getCurrentRevision(\get_class($article), $article->getId());
        static::assertSame('2', (string) $revision);
    }

    public function testDeleteUnInitProxy(): void
    {
        $user = new UserAudit('beberlei');

        $this->em->persist($user);
        $this->em->flush();

        unset($user);
        $this->em->clear();

        $user = $this->em->getReference(UserAudit::class, 1);
        $this->em->remove($user);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
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
        $this->auditManager->getConfiguration()->setUsernameCallable(static function () {
            return 'user: '.uniqid();
        });

        $user = new UserAudit('beberlei');
        $this->em->persist($user);
        $this->em->flush();

        $user->setName('b.eberlei');
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $revisions = $reader->findRevisionHistory();

        static::assertCount(2, $revisions);
        static::assertContainsOnly(Revision::class, $revisions);

        static::assertStringStartsWith('user: ', $revisions[0]->getUsername());
        static::assertStringStartsWith('user: ', $revisions[1]->getUsername());

        static::assertNotSame($revisions[0]->getUsername(), $revisions[1]->getUsername());
    }

    public function testRevisionForeignKeys(): void
    {
        $isSqlitePlatform = 'sqlite' === $this->em->getConnection()->getDatabasePlatform()->getName();
        $updateForeignKeysConfig = false;

        if ($isSqlitePlatform) {
            $updateForeignKeysConfig = '0' === $this->em->getConnection()->executeQuery('PRAGMA foreign_keys;')->fetchOne();

            if ($updateForeignKeysConfig) {
                // Enable the "foreign_keys" pragma.
                $this->em->getConnection()->executeQuery('PRAGMA foreign_keys = ON;');
            }
        }

        $user = new UserAudit('phansys');

        $this->em->persist($user);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $revisions = $reader->findRevisions(\get_class($user), $user->getId());

        static::assertCount(1, $revisions);

        $revision = $reader->getCurrentRevision(\get_class($user), $user->getId());
        static::assertSame('1', (string) $revision);

        $revisionsTableName = $this->auditManager->getConfiguration()->getRevisionTableName();

        $this->expectException(DriverException::class);
        $this->expectExceptionMessageMatches('#SQLSTATE\[[\d]+\]: #');

        try {
            $this->em->getConnection()->delete($revisionsTableName, ['id' => $revision]);
        } catch (DriverException $e) {
            throw $e;
        } finally {
            if ($updateForeignKeysConfig) {
                // Restore the original value for the "foreign_keys" pragma.
                $this->em->getConnection()->executeQuery('PRAGMA foreign_keys = OFF;');
            }
        }
    }
}
