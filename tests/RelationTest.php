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

use Doctrine\Common\Collections\Collection;
use SimpleThings\EntityAudit\ChangedEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\AbstractDataEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\Category;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\CheeseProduct;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\DataContainerEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\DataLegalEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\DataPrivateEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\FoodCategory;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OneToOneAuditedEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OneToOneMasterEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OneToOneNotAuditedEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OwnedEntity1;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OwnedEntity2;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OwnedEntity3;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OwnedEntity4;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\OwnerEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\Page;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\PageAlias;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\PageLocalization;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\Product;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\RelationFoobarEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\RelationOneToOneEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\RelationReferencedEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\WineProduct;

final class RelationTest extends BaseTest
{
    protected $schemaEntities = [
        OwnerEntity::class,
        OwnedEntity1::class,
        OwnedEntity2::class,
        OwnedEntity3::class,
        OwnedEntity4::class,
        OneToOneMasterEntity::class,
        OneToOneAuditedEntity::class,
        OneToOneNotAuditedEntity::class,
        Category::class,
        FoodCategory::class,
        Product::class,
        WineProduct::class,
        CheeseProduct::class,
        Page::class,
        PageAlias::class,
        PageLocalization::class,
        RelationOneToOneEntity::class,
        RelationFoobarEntity::class,
        RelationReferencedEntity::class,
        AbstractDataEntity::class,
        DataLegalEntity::class,
        DataPrivateEntity::class,
        DataContainerEntity::class,
    ];

    protected $auditedEntities = [
        OwnerEntity::class,
        OwnedEntity1::class,
        OneToOneAuditedEntity::class,
        OneToOneMasterEntity::class,
        Category::class,
        FoodCategory::class,
        Product::class,
        WineProduct::class,
        CheeseProduct::class,
        Page::class,
        PageAlias::class,
        PageLocalization::class,
        RelationOneToOneEntity::class,
        RelationFoobarEntity::class,
        RelationReferencedEntity::class,
        AbstractDataEntity::class,
        DataLegalEntity::class,
        DataPrivateEntity::class,
        DataContainerEntity::class,
    ];

    public function testUndefinedIndexesInUOWForRelations(): void
    {
        $owner = new OwnerEntity();
        $owner->setTitle('owner');
        $owned1 = new OwnedEntity1();
        $owned1->setTitle('owned1');
        $owned1->setOwner($owner);
        $owned2 = new OwnedEntity2();
        $owned2->setTitle('owned2');
        $owned2->setOwner($owner);

        $this->em->persist($owner);
        $this->em->persist($owned1);
        $this->em->persist($owned2);

        $this->em->flush();

        unset($owner, $owned1, $owned2);

        $this->em->clear();

        $owner = $this->em->getReference(OwnerEntity::class, 1);
        static::assertNotNull($owner);
        $this->em->remove($owner);
        $owned1 = $this->em->getReference(OwnedEntity1::class, 1);
        static::assertNotNull($owned1);
        $this->em->remove($owned1);
        $owned2 = $this->em->getReference(OwnedEntity2::class, 1);
        static::assertNotNull($owned2);
        $this->em->remove($owned2);

        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        static::assertIsArray($changedEntities);
        static::assertCount(2, $changedEntities);
        $changedOwner = $changedEntities[0]->getEntity();
        $changedOwned = $changedEntities[1]->getEntity();

        static::assertContainsOnly(ChangedEntity::class, $changedEntities);
        static::assertSame(OwnerEntity::class, $changedEntities[0]->getClassName());
        static::assertInstanceOf(OwnerEntity::class, $changedOwner);
        static::assertInstanceOf(OwnedEntity1::class, $changedOwned);
        static::assertSame('DEL', $changedEntities[0]->getRevisionType());
        static::assertSame('DEL', $changedEntities[1]->getRevisionType());
        static::assertArrayHasKey('id', $changedEntities[0]->getId());
        static::assertSame('1', (string) $changedEntities[0]->getId()['id']);
        static::assertArrayHasKey('id', $changedEntities[1]->getId());
        static::assertSame('1', (string) $changedEntities[1]->getId()['id']);
        // uninit proxy messes up ids, it is fine
        static::assertCount(0, $changedOwner->getOwned1());
        static::assertCount(0, $changedOwner->getOwned2());
        static::assertNull($changedOwned->getOwner());
    }

    public function testIssue92(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner1 = new OwnerEntity();
        $owner1->setTitle('test');
        $owner2 = new OwnerEntity();
        $owner2->setTitle('test');

        $this->em->persist($owner1);
        $this->em->persist($owner2);

        $this->em->flush();

        $owned1 = new OwnedEntity1();
        $owned1->setOwner($owner1);
        $owned1->setTitle('test');

        $owned2 = new OwnedEntity1();
        $owned2->setOwner($owner1);
        $owned2->setTitle('test');

        $owned3 = new OwnedEntity1();
        $owned3->setOwner($owner2);
        $owned3->setTitle('test');

        $this->em->persist($owned1);
        $this->em->persist($owned2);
        $this->em->persist($owned3);

        $this->em->flush();

        $owned2->setOwner($owner2);

        $this->em->flush(); // 3

        $owner1Id = $owner1->getId();
        static::assertNotNull($owner1Id);

        $audited = $auditReader->find(OwnerEntity::class, $owner1Id, 3);
        static::assertNotNull($audited);
        static::assertCount(1, $audited->getOwned1());
    }

    public function testOneToOne(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $master = new OneToOneMasterEntity();
        $master->setTitle('master#1');

        $this->em->persist($master);
        $this->em->flush(); // #1

        $notAudited = new OneToOneNotAuditedEntity();
        $notAudited->setTitle('notaudited');

        $this->em->persist($notAudited);

        $master->setNotAudited($notAudited);

        $this->em->flush(); // #2

        $audited = new OneToOneAuditedEntity();
        $audited->setTitle('audited');
        $master->setAudited($audited);

        $this->em->persist($audited);

        $this->em->flush(); // #3

        $audited->setTitle('changed#4');

        $this->em->flush(); // #4

        $master->setTitle('changed#5');

        $this->em->flush(); // #5

        $this->em->remove($audited);

        $this->em->flush(); // #6

        $masterId = $master->getId();
        static::assertNotNull($masterId);

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 1);
        static::assertNotNull($audited);
        static::assertSame('master#1', $audited->getTitle());
        static::assertNull($audited->getAudited());
        static::assertNull($audited->getNotAudited());

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 2);
        static::assertNotNull($audited);
        static::assertSame('master#1', $audited->getTitle());
        static::assertNull($audited->getAudited());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 3);
        static::assertNotNull($audited);
        static::assertSame('master#1', $audited->getTitle());
        $auditedRelation = $audited->getAudited();
        static::assertNotNull($auditedRelation);
        static::assertSame('audited', $auditedRelation->getTitle());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 4);
        static::assertNotNull($audited);
        static::assertSame('master#1', $audited->getTitle());
        $auditedRelation = $audited->getAudited();
        static::assertNotNull($auditedRelation);
        static::assertSame('changed#4', $auditedRelation->getTitle());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());

        $auditReader->setLoadAuditedEntities(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 4);
        static::assertNotNull($audited);
        static::assertNull($audited->getAudited());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());

        $auditReader->setLoadAuditedEntities(true);
        $auditReader->setLoadNativeEntities(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 4);
        static::assertNotNull($audited);
        $auditedRelation = $audited->getAudited();
        static::assertNotNull($auditedRelation);
        static::assertSame('changed#4', $auditedRelation->getTitle());
        static::assertNull($audited->getNotAudited());

        $auditReader->setLoadNativeEntities(true);

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 5);
        static::assertNotNull($audited);
        static::assertSame('changed#5', $audited->getTitle());
        $auditedRelation = $audited->getAudited();
        static::assertNotNull($auditedRelation);
        static::assertSame('changed#4', $auditedRelation->getTitle());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());

        $audited = $auditReader->find(OneToOneMasterEntity::class, $masterId, 6);
        static::assertNotNull($audited);
        static::assertSame('changed#5', $audited->getTitle());
        static::assertNull($audited->getAudited());
        $notAuditedRelation = $audited->getNotAudited();
        static::assertNotNull($notAuditedRelation);
        static::assertSame('notaudited', $notAuditedRelation->getTitle());
    }

    public function testManyToMany(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner = new OwnerEntity();
        $owner->setTitle('owner#1');

        $owned31 = new OwnedEntity3();
        $owned31->setTitle('owned3#1');
        $owner->addOwned3($owned31);

        $owned32 = new OwnedEntity3();
        $owned32->setTitle('owned3#2');
        $owner->addOwned3($owned32);

        $owned4 = new OwnedEntity4();
        $owned4->setTitle('owned4');
        $owned4->addOwner($owner);

        $this->em->persist($owner);
        $this->em->persist($owned31);
        $this->em->persist($owned32);
        $this->em->persist($owned4);

        $this->em->flush(); // #1

        $ownerId = $owner->getId();
        static::assertNotNull($ownerId);

        // owned3 is a m:n relationship with OwnerEntity as the owning side
        // checking that getOwned3() returns a collection of owned entities
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 1);

        static::assertNotNull($audited);
        static::assertInstanceOf(Collection::class, $audited->getOwned3());
        static::assertCount(2, $audited->getOwned3());

        // ownedInverse is a m:n relationship with OwnerEntity as the inverse side
        // checking the getOwnedInverse returns a collection of current owned4 entities
        static::assertInstanceOf(Collection::class, $audited->getOwnedInverse());
        static::assertCount(1, $audited->getOwnedInverse());
    }

    /**
     * @group mysql
     */
    public function testRelations(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        // create owner
        $owner = new OwnerEntity();
        $owner->setTitle('rev#1');

        $this->em->persist($owner);
        $this->em->flush();

        $ownerId = $owner->getId();
        static::assertNotNull($ownerId);

        static::assertCount(1, $auditReader->findRevisions(OwnerEntity::class, $ownerId));

        // create un-managed entity
        $owned21 = new OwnedEntity2();
        $owned21->setTitle('owned21');
        $owned21->setOwner($owner);

        $this->em->persist($owned21);
        $this->em->flush();

        // should not add a revision
        static::assertCount(1, $auditReader->findRevisions(OwnerEntity::class, $ownerId));

        $owner->setTitle('changed#2');

        $this->em->flush();

        // should add a revision
        static::assertCount(2, $auditReader->findRevisions(OwnerEntity::class, $ownerId));

        $owned11 = new OwnedEntity1();
        $owned11->setTitle('created#3');
        $owned11->setOwner($owner);

        $this->em->persist($owned11);

        $this->em->flush();

        // should not add a revision for owner
        static::assertCount(2, $auditReader->findRevisions(OwnerEntity::class, $ownerId));
        // should add a revision for owned
        $owned11Id = $owned11->getId();
        static::assertNotNull($owned11Id);
        static::assertCount(1, $auditReader->findRevisions(OwnedEntity1::class, $owned11Id));

        // should not mess foreign keys
        $rows = $this->em->getConnection()->fetchAllAssociative('SELECT strange_owned_id_name FROM OwnedEntity1');
        static::assertSame($ownerId, (int) $rows[0]['strange_owned_id_name']);
        $this->em->refresh($owner);
        static::assertCount(1, $owner->getOwned1());
        static::assertCount(1, $owner->getOwned2());

        // we have a third revision where Owner with title changed#2 has one owned2 and one owned1 entity with title created#3
        $owned12 = new OwnedEntity1();
        $owned12->setTitle('created#4');
        $owned12->setOwner($owner);

        $this->em->persist($owned12);
        $this->em->flush();

        // we have a forth revision where Owner with title changed#2 has one owned2 and two owned1 entities (created#3, created#4)
        $owner->setTitle('changed#5');

        $this->em->flush();
        // we have a fifth revision where Owner with title changed#5 has one owned2 and two owned1 entities (created#3, created#4)

        $owner->setTitle('changed#6');
        $owned12->setTitle('changed#6');

        $this->em->flush();

        $this->em->remove($owned11);
        $owned12->setTitle('changed#7');
        $owner->setTitle('changed#7');
        $this->em->flush();
        // we have a seventh revision where Owner with title changed#7 has one owned2 and one owned1 entity (changed#7)

        $ownerId = $owner->getId();
        static::assertNotNull($ownerId);

        // checking third revision
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 3);
        static::assertNotNull($audited);
        static::assertInstanceOf(Collection::class, $audited->getOwned2());
        static::assertSame('changed#2', $audited->getTitle());

        static::assertCount(1, $audited->getOwned1());
        $o1 = $audited->getOwned1();
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame('created#3', $firstO1->getTitle());
        static::assertCount(1, $audited->getOwned2());

        $o2 = $audited->getOwned2();
        $firstO2 = $o2->first();
        static::assertNotFalse($firstO2);
        static::assertSame('owned21', $firstO2->getTitle());

        // checking forth revision
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 4);
        static::assertNotNull($audited);
        static::assertSame('changed#2', $audited->getTitle());

        static::assertCount(2, $audited->getOwned1());
        $o1 = $audited->getOwned1();
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame('created#3', $firstO1->getTitle());
        $lastO1 = $o1->last();
        static::assertNotFalse($lastO1);
        static::assertSame('created#4', $lastO1->getTitle());

        static::assertCount(1, $audited->getOwned2());
        $o2 = $audited->getOwned2();
        $firstO2 = $o2->first();
        static::assertNotFalse($firstO2);
        static::assertSame('owned21', $firstO2->getTitle());

        // check skipping collections
        $auditReader->setLoadAuditedCollections(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 4);
        static::assertNotNull($audited);
        static::assertCount(0, $audited->getOwned1());
        static::assertCount(1, $audited->getOwned2());

        $auditReader->setLoadNativeCollections(false);
        $auditReader->setLoadAuditedCollections(true);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 4);
        static::assertNotNull($audited);
        static::assertCount(2, $audited->getOwned1());
        static::assertCount(0, $audited->getOwned2());

        // checking fifth revision
        $auditReader->setLoadNativeCollections(true);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 5);
        static::assertNotNull($audited);
        static::assertSame('changed#5', $audited->getTitle());

        static::assertCount(2, $audited->getOwned1());
        $o1 = $audited->getOwned1();
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame('created#3', $firstO1->getTitle());
        $lastO1 = $o1->last();
        static::assertNotFalse($lastO1);
        static::assertSame('created#4', $lastO1->getTitle());

        static::assertCount(1, $audited->getOwned2());
        $o2 = $audited->getOwned2();
        $firstO2 = $o2->first();
        static::assertNotFalse($firstO2);
        static::assertSame('owned21', $firstO2->getTitle());

        // checking sixth revision
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 6);
        static::assertNotNull($audited);
        static::assertSame('changed#6', $audited->getTitle());

        static::assertCount(2, $audited->getOwned1());
        $o1 = $audited->getOwned1();
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame('created#3', $firstO1->getTitle());
        $lastO1 = $o1->last();
        static::assertNotFalse($lastO1);
        static::assertSame('changed#6', $lastO1->getTitle());

        static::assertCount(1, $audited->getOwned2());
        $o2 = $audited->getOwned2();
        $firstO2 = $o2->first();
        static::assertNotFalse($firstO2);
        static::assertSame('owned21', $firstO2->getTitle());

        // checking seventh revision
        $audited = $auditReader->find(OwnerEntity::class, $ownerId, 7);
        static::assertNotNull($audited);
        static::assertSame('changed#7', $audited->getTitle());

        static::assertCount(1, $audited->getOwned1());
        $o1 = $audited->getOwned1();
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame('changed#7', $firstO1->getTitle());

        static::assertCount(1, $audited->getOwned2());
        $o2 = $audited->getOwned2();
        $firstO2 = $o2->first();
        static::assertNotFalse($firstO2);
        static::assertSame('owned21', $firstO2->getTitle());

        $history = $auditReader->getEntityHistory(OwnerEntity::class, $ownerId);

        static::assertCount(5, $history);
    }

    /**
     * @group mysql
     */
    public function testRemoval(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner1 = new OwnerEntity();
        $owner1->setTitle('owner1');

        $owner2 = new OwnerEntity();
        $owner2->setTitle('owner2');

        $owned1 = new OwnedEntity1();
        $owned1->setTitle('owned1');
        $owned1->setOwner($owner1);

        $owned2 = new OwnedEntity1();
        $owned2->setTitle('owned2');
        $owned2->setOwner($owner1);

        $owned3 = new OwnedEntity1();
        $owned3->setTitle('owned3');
        $owned3->setOwner($owner1);

        $this->em->persist($owner1);
        $this->em->persist($owner2);
        $this->em->persist($owned1);
        $this->em->persist($owned2);
        $this->em->persist($owned3);

        $this->em->flush(); // #1

        $owned1->setOwner($owner2);
        $this->em->flush(); // #2

        $this->em->remove($owned1);
        $this->em->flush(); // #3

        $owned2->setTitle('updated owned2');
        $this->em->flush(); // #4

        $this->em->remove($owned2);
        $this->em->flush(); // #5

        $this->em->remove($owned3);
        $this->em->flush(); // #6

        $owner1Id = $owner1->getId();
        static::assertNotNull($owner1Id);

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 1);
        static::assertNotNull($owner);
        static::assertCount(3, $owner->getOwned1());

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 2);
        static::assertNotNull($owner);
        static::assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 3);
        static::assertNotNull($owner);
        static::assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 4);
        static::assertNotNull($owner);
        static::assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 5);
        static::assertNotNull($owner);
        static::assertCount(1, $owner->getOwned1());

        $owner = $auditReader->find(OwnerEntity::class, $owner1Id, 6);
        static::assertNotNull($owner);
        static::assertCount(0, $owner->getOwned1());
    }

    /**
     * @group mysql
     */
    public function testDetaching(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner = new OwnerEntity();
        $owner->setTitle('created#1');

        $owned = new OwnedEntity1();
        $owned->setTitle('created#1');

        $this->em->persist($owner);
        $this->em->persist($owned);

        $this->em->flush(); // #1

        $ownerId1 = $owner->getId();
        static::assertNotNull($ownerId1);
        $ownedId1 = $owned->getId();
        static::assertNotNull($ownedId1);

        $owned->setTitle('associated#2');
        $owned->setOwner($owner);

        $this->em->flush(); // #2

        $owned->setTitle('deassociated#3');
        $owned->setOwner(null);

        $this->em->flush(); // #3

        $owned->setTitle('associated#4');
        $owned->setOwner($owner);

        $this->em->flush(); // #4

        $this->em->remove($owned);

        $this->em->flush(); // #5

        $owned = new OwnedEntity1();
        $owned->setTitle('recreated#6');
        $owned->setOwner($owner);

        $this->em->persist($owned);
        $this->em->flush(); // #6

        $ownedId2 = $owned->getId();
        static::assertNotNull($ownedId2);

        $this->em->remove($owner);
        $this->em->flush(); // #7

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 1);
        static::assertNotNull($auditedEntity);
        static::assertSame('created#1', $auditedEntity->getTitle());
        static::assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 2);
        static::assertNotNull($auditedEntity);

        $o1 = $auditedEntity->getOwned1();
        static::assertCount(1, $o1);
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame($ownedId1, $firstO1->getId());

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 3);
        static::assertNotNull($auditedEntity);
        static::assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 4);
        static::assertNotNull($auditedEntity);
        static::assertCount(1, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 5);
        static::assertNotNull($auditedEntity);
        static::assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(OwnerEntity::class, $ownerId1, 6);
        static::assertNotNull($auditedEntity);

        $o1 = $auditedEntity->getOwned1();
        static::assertCount(1, $o1);
        $firstO1 = $o1->first();
        static::assertNotFalse($firstO1);
        static::assertSame($ownedId2, $firstO1->getId());

        $auditedEntity = $auditReader->find(OwnedEntity1::class, $ownedId2, 7);
        static::assertNotNull($auditedEntity);
        static::assertNull($auditedEntity->getOwner());
    }

    public function testOneXRelations(): void
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner = new OwnerEntity();
        $owner->setTitle('owner');

        $owned = new OwnedEntity1();
        $owned->setTitle('owned');
        $owned->setOwner($owner);

        $this->em->persist($owner);
        $this->em->persist($owned);

        $this->em->flush();
        // first revision done

        $owner->setTitle('changed#2');
        $owned->setTitle('changed#2');
        $this->em->flush();

        $ownerId = $owner->getId();
        static::assertNotNull($ownerId);

        // checking first revision
        $audited = $auditReader->find(OwnedEntity1::class, $ownerId, 1);
        static::assertNotNull($audited);
        static::assertSame('owned', $audited->getTitle());

        $auditedOwner = $audited->getOwner();
        static::assertNotNull($auditedOwner);
        static::assertSame('owner', $auditedOwner->getTitle());

        // checking second revision
        $audited = $auditReader->find(OwnedEntity1::class, $ownerId, 2);
        static::assertNotNull($audited);
        static::assertSame('changed#2', $audited->getTitle());

        $auditedOwner = $audited->getOwner();
        static::assertNotNull($auditedOwner);
        static::assertSame('changed#2', $auditedOwner->getTitle());
    }

    public function testOneToManyJoinedInheritance(): void
    {
        $food = new FoodCategory();
        $this->em->persist($food);

        $parmesanCheese = new CheeseProduct('Parmesan');
        $this->em->persist($parmesanCheese);

        $cheddarCheese = new CheeseProduct('Cheddar');
        $this->em->persist($cheddarCheese);

        $vine = new WineProduct('Champagne');
        $this->em->persist($vine);

        $food->addProduct($parmesanCheese);
        $food->addProduct($cheddarCheese);
        $food->addProduct($vine);

        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $foodId = $food->getId();
        static::assertNotNull($foodId);

        $currentRevision = $reader->getCurrentRevision(FoodCategory::class, $foodId);
        static::assertNotNull($currentRevision);

        $auditedFood = $reader->find(FoodCategory::class, $foodId, $currentRevision);

        static::assertInstanceOf(FoodCategory::class, $auditedFood);
        static::assertCount(3, $auditedFood->getProducts());

        [$productOne, $productTwo, $productThree] = $auditedFood->getProducts()->toArray();

        static::assertInstanceOf(\get_class($parmesanCheese), $productOne);
        static::assertInstanceOf(\get_class($cheddarCheese), $productTwo);
        static::assertInstanceOf(\get_class($vine), $productThree);

        static::assertSame($parmesanCheese->getId(), $productOne->getId());
        static::assertSame($cheddarCheese->getId(), $productTwo->getId());
    }

    public function testOneToManyWithIndexBy(): void
    {
        $page = new Page();
        $this->em->persist($page);

        $gbLocalization = new PageLocalization('en-GB');
        $this->em->persist($gbLocalization);

        $usLocalization = new PageLocalization('en-US');
        $this->em->persist($usLocalization);

        $page->addLocalization($gbLocalization);
        $page->addLocalization($usLocalization);

        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $pageId = $page->getId();
        static::assertNotNull($pageId);

        $currentRevision = $reader->getCurrentRevision(Page::class, $pageId);
        static::assertNotNull($currentRevision);

        $auditedPage = $reader->find(Page::class, $pageId, $currentRevision);
        static::assertNotNull($auditedPage);

        static::assertNotEmpty($auditedPage->getLocalizations());

        static::assertCount(2, $auditedPage->getLocalizations());

        static::assertNotEmpty($auditedPage->getLocalizations()->get('en-US'));
        static::assertNotEmpty($auditedPage->getLocalizations()->get('en-GB'));
    }

    /**
     * @group mysql
     */
    public function testOneToManyCollectionDeletedElements(): void
    {
        $owner = new OwnerEntity();
        $this->em->persist($owner);

        $ownedOne = new OwnedEntity1();
        $ownedOne->setTitle('Owned#1');
        $ownedOne->setOwner($owner);
        $this->em->persist($ownedOne);

        $ownedTwo = new OwnedEntity1();
        $ownedTwo->setTitle('Owned#2');
        $ownedTwo->setOwner($owner);
        $this->em->persist($ownedTwo);

        $ownedThree = new OwnedEntity1();
        $ownedThree->setTitle('Owned#3');
        $ownedThree->setOwner($owner);
        $this->em->persist($ownedThree);

        $ownedFour = new OwnedEntity1();
        $ownedFour->setTitle('Owned#4');
        $ownedFour->setOwner($owner);
        $this->em->persist($ownedFour);

        $owner->addOwned1($ownedOne);
        $owner->addOwned1($ownedTwo);
        $owner->addOwned1($ownedThree);
        $owner->addOwned1($ownedFour);

        $owner->setTitle('Owner with four owned elements.');
        $this->em->flush(); // #1

        $owner->setTitle('Owner with three owned elements.');
        $this->em->remove($ownedTwo);

        $this->em->flush(); // #2

        $owner->setTitle('Just another revision.');

        $this->em->flush(); // #3

        $reader = $this->auditManager->createAuditReader($this->em);

        $ownerId = $owner->getId();
        static::assertNotNull($ownerId);

        $currentRevision = $reader->getCurrentRevision(OwnerEntity::class, $ownerId);
        static::assertNotNull($currentRevision);

        $auditedOwner = $reader->find(OwnerEntity::class, $ownerId, $currentRevision);
        static::assertNotNull($auditedOwner);

        static::assertCount(3, $auditedOwner->getOwned1());

        $ids = [];
        foreach ($auditedOwner->getOwned1() as $ownedElement) {
            $ids[] = $ownedElement->getId();
        }

        static::assertTrue(\in_array($ownedOne->getId(), $ids, true));
        static::assertTrue(\in_array($ownedThree->getId(), $ids, true));
        static::assertTrue(\in_array($ownedFour->getId(), $ids, true));
    }

    public function testOneToOneEdgeCase(): void
    {
        $base = new RelationOneToOneEntity();

        $referenced = new RelationFoobarEntity();
        $referenced->setFoobarField('foobar');
        $referenced->setReferencedField('referenced');

        $base->setReferencedEntity($referenced);
        $referenced->setOneToOne($base);

        $this->em->persist($base);
        $this->em->persist($referenced);

        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $baseId = $base->getId();
        static::assertNotNull($baseId);

        $auditedBase = $reader->find(RelationOneToOneEntity::class, $baseId, 1);
        static::assertNotNull($auditedBase);

        $referencedEntity = $auditedBase->getReferencedEntity();
        static::assertInstanceOf(RelationFoobarEntity::class, $referencedEntity);
        static::assertSame('foobar', $referencedEntity->getFoobarField());
        static::assertSame('referenced', $referencedEntity->getReferencedField());
    }

    /**
     * Specific test for the case where a join condition is via an ORM/Id and where the column is also an object.
     * Used to result in an 'aray to string conversion' error.
     *
     * @doesNotPerformAssertions
     */
    public function testJoinOnObject(): void
    {
        $page = new Page();
        $this->em->persist($page);
        $this->em->flush();

        $pageAlias = new PageAlias($page, 'This is the alias');
        $this->em->persist($pageAlias);
        $this->em->flush();
    }

    public function testOneToOneBidirectional(): void
    {
        $private1 = new DataPrivateEntity();
        $private1->setName('private1');

        $legal1 = new DataLegalEntity();
        $legal1->setCompany('legal1');

        $legal2 = new DataLegalEntity();
        $legal2->setCompany('legal2');

        $container1 = new DataContainerEntity();
        $container1->setData($private1);
        $container1->setName('container1');

        $container2 = new DataContainerEntity();
        $container2->setData($legal1);
        $container2->setName('container2');

        $container3 = new DataContainerEntity();
        $container3->setData($legal2);
        $container3->setName('container3');

        $this->em->persist($container1);
        $this->em->persist($container2);
        $this->em->persist($container3);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $legal2Id = $legal2->getId();
        static::assertNotNull($legal2Id);

        $legal2Base = $reader->find(DataLegalEntity::class, $legal2Id, 1);
        static::assertNotNull($legal2Base);
        $dataContainer = $legal2Base->getDataContainer();
        static::assertNotNull($dataContainer);
        static::assertSame('container3', $dataContainer->getName());
    }
}
