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

use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Cat;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Dog;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Fox;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\Rabbit;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit;

class CoreTest extends BaseTest
{
    protected $schemaEntities = [
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\ProfileAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\AnimalAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Fox',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Rabbit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\PetAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Cat',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Dog',
    ];

    protected $auditedEntities = [
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\ProfileAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\AnimalAudit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Rabbit',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Fox',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Cat',
        'SimpleThings\EntityAudit\Tests\Fixtures\Core\Dog',
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

        $this->assertCount(1, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        $this->assertCount(1, $this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit'));
        $this->assertCount(1, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        $this->assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));

        $article->setText('oeruoa');
        $rabbit->setName('Rabbit');
        $rabbit->setColor('gray');
        $foxy->setName('Foxy');
        $foxy->setTailLength(55);

        $this->em->flush();

        $this->assertCount(2, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        $this->assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        $this->assertCount(4, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));

        $this->em->remove($user);
        $this->em->remove($article);
        $this->em->remove($rabbit);
        $this->em->remove($foxy);
        $this->em->flush();

        $this->assertCount(3, $this->em->getConnection()->fetchAll('SELECT id FROM revisions'));
        $this->assertCount(2, $this->em->getConnection()->fetchAll('SELECT * FROM UserAudit_audit'));
        $this->assertCount(3, $this->em->getConnection()->fetchAll('SELECT * FROM ArticleAudit_audit'));
        $this->assertCount(6, $this->em->getConnection()->fetchAll('SELECT * FROM AnimalAudit_audit'));
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

        $this->assertInstanceOf(\get_class($user), $auditUser, 'Audited User is also a User instance.');
        $this->assertEquals($user->getId(), $auditUser->getId(), 'Ids of audited user and real user should be the same.');
        $this->assertEquals($user->getName(), $auditUser->getName(), 'Name of audited user and real user should be the same.');
        $this->assertFalse($this->em->contains($auditUser), 'Audited User should not be in the identity map.');
        $this->assertNotSame($user, $auditUser, 'User and Audited User instances are not the same.');

        $auditFox = $reader->find(\get_class($foxy), $foxy->getId(), 1);

        $this->assertInstanceOf(\get_class($foxy), $auditFox, "Audited SINGLE_TABLE class keeps it's class.");
        $this->assertEquals($foxy->getId(), $auditFox->getId(), 'Ids of audited SINGLE_TABLE class and real SINGLE_TABLE class should be the same.');
        $this->assertEquals($foxy->getName(), $auditFox->getName(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        $this->assertEquals($foxy->getTailLength(), $auditFox->getTailLength(), 'Loaded and original attributes should be the same for SINGLE_TABLE inheritance.');
        $this->assertFalse($this->em->contains($auditFox), 'Audited SINGLE_TABLE inheritance class should not be in the identity map.');
        $this->assertNotSame($this, $auditFox, 'Audited and new entities should not be the same object for SINGLE_TABLE inheritance.');

        $auditCat = $reader->find(\get_class($cat), $cat->getId(), 1);

        $this->assertInstanceOf(\get_class($cat), $auditCat, "Audited JOINED class keeps it's class.");
        $this->assertEquals($cat->getId(), $auditCat->getId(), 'Ids of audited JOINED class and real JOINED class should be the same.');
        $this->assertEquals($cat->getName(), $auditCat->getName(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        $this->assertEquals($cat->getColor(), $auditCat->getColor(), 'Loaded and original attributes should be the same for JOINED inheritance.');
        $this->assertFalse($this->em->contains($auditCat), 'Audited JOINED inheritance class should not be in the identity map.');
        $this->assertNotSame($this, $auditCat, 'Audited and new entities should not be the same object for JOINED inheritance.');
    }

    public function testFindNoRevisionFound(): void
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->expectException(NoRevisionFoundException::class);
        $this->expectExceptionMessage(
            "No revision of class 'SimpleThings\\EntityAudit\\Tests\\Fixtures\\Core\\UserAudit' (1) was found at revision 1 or before. The entity did not exist at the specified revision yet."
        );

        $reader->find('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit', 1, 1);
    }

    public function testFindNotAudited(): void
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->expectException(NotAuditedException::class);
        $this->expectExceptionMessage("Class 'stdClass' is not audited.");

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

        $this->assertCount(2, $revisions);
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());
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
        $this->assertCount(6, $changedEntities);
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);

        $this->assertEquals('SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('INS', $changedEntities[0]->getRevisionType());
        $this->assertEquals(['id' => 1], $changedEntities[0]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\Fixtures\Core\ArticleAudit', $changedEntities[0]->getEntity());

        $this->assertEquals('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit', $changedEntities[1]->getClassName());
        $this->assertEquals('INS', $changedEntities[1]->getRevisionType());
        $this->assertEquals(['id' => 1], $changedEntities[1]->getId());
        $this->assertInstanceOf('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit', $changedEntities[1]->getEntity());
    }

    public function testNotVersionedRelationFind(): void
    {
        // Insert user without the manager to skip revision registering.
        $this->em->getConnection()->insert(
            $this->em->getClassMetadata('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit')->getTableName(),
            [
                'id' => 1,
                'name' => 'beberlei',
            ]
        );

        $article = new ArticleAudit(
            'test',
            'yadda!',
            $this->em->getRepository('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit')->find(1),
            'text'
        );

        $this->em->persist($article);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $this->assertSame('beberlei', $reader->find(\get_class($article), 1, 1)->getAuthor()->getName());
    }

    public function testNotVersionedReverseRelationFind(): void
    {
        $user = new UserAudit('beberlei');

        $this->em->persist($user);
        $this->em->flush();

        // Insert user without the manager to skip revision registering.
        $this->em->getConnection()->insert(
            $this->em->getClassMetadata('SimpleThings\EntityAudit\Tests\Fixtures\Core\ProfileAudit')->getTableName(),
            [
                'id' => 1,
                'biography' => 'He is an amazing contributor!',
                'user_id' => 1,
            ]
        );

        $reader = $this->auditManager->createAuditReader($this->em);

        $this->assertSame('He is an amazing contributor!', $reader->find(\get_class($user), 1, 1)->getProfile()->getBiography());
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

        $this->assertCount(2, $revisions);
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertEquals(2, $revisions[0]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[0]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[0]->getUsername());

        $this->assertEquals(1, $revisions[1]->getRev());
        $this->assertInstanceOf('DateTime', $revisions[1]->getTimestamp());
        $this->assertEquals('beberlei', $revisions[1]->getUsername());

        //SINGLE_TABLE should have separate revision history
        $this->assertCount(2, $reader->findRevisions(\get_class($foxy), $foxy->getId()));
        $this->assertCount(1, $reader->findRevisions(\get_class($rabbit), $rabbit->getId()));
        //JOINED too
        $this->assertCount(2, $reader->findRevisions(\get_class($dog), $dog->getId()));
        $this->assertCount(1, $reader->findRevisions(\get_class($cat), $cat->getId()));
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
        $this->assertEquals(2, $revision);

        $user->setName('David');
        $this->em->flush();

        $revision = $reader->getCurrentRevision(\get_class($user), $user->getId());
        $this->assertEquals(3, $revision);
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
        $this->assertEquals(2, $revision);

        $article->setIgnoreme('textnew');
        $this->em->persist($article);
        $this->em->flush();

        $revision = $reader->getCurrentRevision(\get_class($article), $article->getId());
        $this->assertEquals(2, $revision);
    }

    public function testDeleteUnInitProxy(): void
    {
        $user = new UserAudit('beberlei');

        $this->em->persist($user);
        $this->em->flush();

        unset($user);
        $this->em->clear();

        $user = $this->em->getReference('SimpleThings\\EntityAudit\\Tests\\Fixtures\\Core\\UserAudit', 1);
        $this->em->remove($user);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        $this->assertCount(1, $changedEntities);
        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);
        $this->assertEquals('SimpleThings\EntityAudit\Tests\Fixtures\Core\UserAudit', $changedEntities[0]->getClassName());
        $this->assertEquals('DEL', $changedEntities[0]->getRevisionType());
        $this->assertEquals(['id' => 1], $changedEntities[0]->getId());
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

        $this->assertCount(2, $revisions);
        $this->assertContainsOnly('SimpleThings\EntityAudit\Revision', $revisions);

        $this->assertStringStartsWith('user: ', $revisions[0]->getUsername());
        $this->assertStringStartsWith('user: ', $revisions[1]->getUsername());

        $this->assertNotEquals($revisions[0]->getUsername(), $revisions[1]->getUsername());
    }
}
