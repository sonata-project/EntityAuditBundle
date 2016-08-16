<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\Tests\EntityAudit;

use SimpleThings\Tests\EntityAudit\Fixtures\Relation\CheeseProduct;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\FoodCategory;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneAuditedEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneMasterEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneNotAuditedEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity1;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity2;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity3;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnerEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\Page;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\PageLocalization;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationFoobarEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationOneToOneEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Relation\WineProduct;

class RelationTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnerEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity1',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity2',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity3',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneMasterEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneAuditedEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneNotAuditedEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Category',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\FoodCategory',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Product',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\WineProduct',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\CheeseProduct',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Page',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\PageLocalization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationOneToOneEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationFoobarEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationReferencedEntity'
    );

    protected $auditedEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnerEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity1',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneAuditedEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\OneToOneMasterEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Category',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\FoodCategory',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Product',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\WineProduct',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\CheeseProduct',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\Page',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\PageLocalization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationOneToOneEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationFoobarEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Relation\RelationReferencedEntity'
    );

    public function testUndefinedIndexesInUOWForRelations()
    {
        $owner = new OwnerEntity();
        $owner->setTitle('owner');
        $owned1 = new OwnedEntity1();
        $owned1->setTitle('owned1');
        $owned1->setOwner($owner);
        $owned2 = new OwnedEntity2();
        $owned2->setTitle('owned2');
        $owned2->setOwner($owner);

        $this->_em->persist($owner);
        $this->_em->persist($owned1);
        $this->_em->persist($owned2);

        $this->_em->flush();

        unset($owner); unset($owned1); unset($owned2);
        $this->_em->clear();

        $owner = $this->_em->getReference("SimpleThings\\Tests\\EntityAudit\\Fixtures\\Relation\\OwnerEntity", 1);
        $this->_em->remove($owner);
        $owned1 = $this->_em->getReference("SimpleThings\\Tests\\EntityAudit\\Fixtures\\Relation\\OwnedEntity1", 1);
        $this->_em->remove($owned1);
        $owned2 = $this->_em->getReference("SimpleThings\\Tests\\EntityAudit\\Fixtures\\Relation\\OwnedEntity2", 1);
        $this->_em->remove($owned2);

        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);
        $changedEntities = $reader->findEntitiesChangedAtRevision(2);

        $this->assertEquals(2, count($changedEntities));
        $changedOwner = $changedEntities[0]->getEntity();
        $changedOwned = $changedEntities[1]->getEntity();

        $this->assertContainsOnly('SimpleThings\EntityAudit\ChangedEntity', $changedEntities);
        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnerEntity', $changedEntities[0]->getClassName());
        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnerEntity', get_class($changedOwner));
        $this->assertEquals('SimpleThings\Tests\EntityAudit\Fixtures\Relation\OwnedEntity1', get_class($changedOwned));
        $this->assertEquals('DEL', $changedEntities[0]->getRevisionType());
        $this->assertEquals('DEL', $changedEntities[1]->getRevisionType());
        $this->assertEquals(array('id' => 1), $changedEntities[0]->getId());
        $this->assertEquals(array('id' => 1), $changedEntities[1]->getId());
        //uninit proxy messes up ids, it is fine
        $this->assertCount(0, $changedOwner->getOwned1());
        $this->assertCount(0, $changedOwner->getOwned2());
        $this->assertNull($changedOwned->getOwner());
    }

    public function testIssue92()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $owner1 = new OwnerEntity();
        $owner1->setTitle('test');
        $owner2 = new OwnerEntity();
        $owner2->setTitle('test');

        $this->_em->persist($owner1);
        $this->_em->persist($owner2);

        $this->_em->flush();

        $owned1 = new OwnedEntity1();
        $owned1->setOwner($owner1);
        $owned1->setTitle('test');

        $owned2 = new OwnedEntity1();
        $owned2->setOwner($owner1);
        $owned2->setTitle('test');

        $owned3 = new OwnedEntity1();
        $owned3->setOwner($owner2);
        $owned3->setTitle('test');

        $this->_em->persist($owned1);
        $this->_em->persist($owned2);
        $this->_em->persist($owned3);

        $this->_em->flush();

        $owned2->setOwner($owner2);

        $this->_em->flush(); //3

        $audited = $auditReader->find(get_class($owner1), $owner1->getId(), 3);

        $this->assertCount(1, $audited->getOwned1());
    }

    public function testOneToOne()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $master = new OneToOneMasterEntity();
        $master->setTitle('master#1');

        $this->_em->persist($master);
        $this->_em->flush(); //#1

        $notAudited = new OneToOneNotAuditedEntity();
        $notAudited->setTitle('notaudited');

        $this->_em->persist($notAudited);

        $master->setNotAudited($notAudited);

        $this->_em->flush(); //#2

        $audited = new OneToOneAuditedEntity();
        $audited->setTitle('audited');
        $master->setAudited($audited);

        $this->_em->persist($audited);

        $this->_em->flush(); //#3

        $audited->setTitle('changed#4');

        $this->_em->flush(); //#4

        $master->setTitle('changed#5');

        $this->_em->flush(); //#5

        $this->_em->remove($audited);

        $this->_em->flush(); //#6

        $audited = $auditReader->find(get_class($master), $master->getId(), 1);
        $this->assertEquals('master#1', $audited->getTitle());
        $this->assertEquals(null, $audited->getAudited());
        $this->assertEquals(null, $audited->getNotAudited());

        $audited = $auditReader->find(get_class($master), $master->getId(), 2);
        $this->assertEquals('master#1', $audited->getTitle());
        $this->assertEquals(null, $audited->getAudited());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $audited = $auditReader->find(get_class($master), $master->getId(), 3);
        $this->assertEquals('master#1', $audited->getTitle());
        $this->assertEquals('audited', $audited->getAudited()->getTitle());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $audited = $auditReader->find(get_class($master), $master->getId(), 4);
        $this->assertEquals('master#1', $audited->getTitle());
        $this->assertEquals('changed#4', $audited->getAudited()->getTitle());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $auditReader->setLoadAuditedEntities(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(get_class($master), $master->getId(), 4);
        $this->assertEquals(null, $audited->getAudited());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $auditReader->setLoadAuditedEntities(true);
        $auditReader->setLoadNativeEntities(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(get_class($master), $master->getId(), 4);
        $this->assertEquals('changed#4', $audited->getAudited()->getTitle());
        $this->assertEquals(null, $audited->getNotAudited());

        $auditReader->setLoadNativeEntities(true);

        $audited = $auditReader->find(get_class($master), $master->getId(), 5);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertEquals('changed#4', $audited->getAudited()->getTitle());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $audited = $auditReader->find(get_class($master), $master->getId(), 6);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertEquals(null, $audited->getAudited());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());
    }

    /**
     * This test verifies the temporary behaviour of audited entities with M-M relationships
     * until https://github.com/simplethings/EntityAudit/issues/85 is implemented
     */
    public function testManyToMany()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $owner = new OwnerEntity();
        $owner->setTitle('owner#1');

        $owned31 = new OwnedEntity3();
        $owned31->setTitle('owned3#1');
        $owner->addOwned3($owned31);

        $owned32 = new OwnedEntity3();
        $owned32->setTitle('owned3#2');
        $owner->addOwned3($owned32);

        $this->_em->persist($owner);
        $this->_em->persist($owned31);
        $this->_em->persist($owned32);

        $this->_em->flush(); //#1

        //checking that getOwned3() returns an empty collection
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 1);
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $audited->getOwned3());
        $this->assertCount(0, $audited->getOwned3());
    }

    /**
     * @group mysql
     */
    public function testRelations()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        //create owner
        $owner = new OwnerEntity();
        $owner->setTitle('rev#1');

        $this->_em->persist($owner);
        $this->_em->flush();

        $this->assertCount(1, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        //create un-managed entity
        $owned21 = new OwnedEntity2();
        $owned21->setTitle('owned21');
        $owned21->setOwner($owner);

        $this->_em->persist($owned21);
        $this->_em->flush();

        //should not add a revision
        $this->assertCount(1, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        $owner->setTitle('changed#2');

        $this->_em->flush();

        //should add a revision
        $this->assertCount(2, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        $owned11 = new OwnedEntity1();
        $owned11->setTitle('created#3');
        $owned11->setOwner($owner);

        $this->_em->persist($owned11);

        $this->_em->flush();

        //should not add a revision for owner
        $this->assertCount(2, $auditReader->findRevisions(get_class($owner), $owner->getId()));
        //should add a revision for owned
        $this->assertCount(1, $auditReader->findRevisions(get_class($owned11), $owned11->getId()));

        //should not mess foreign keys
        $rows = $this->_em->getConnection()->fetchAll('SELECT strange_owned_id_name FROM OwnedEntity1');
        $this->assertEquals($owner->getId(), $rows[0]['strange_owned_id_name']);
        $this->_em->refresh($owner);
        $this->assertCount(1, $owner->getOwned1());
        $this->assertCount(1, $owner->getOwned2());

        //we have a third revision where Owner with title changed#2 has one owned2 and one owned1 entity with title created#3
        $owned12 = new OwnedEntity1();
        $owned12->setTitle('created#4');
        $owned12->setOwner($owner);

        $this->_em->persist($owned12);
        $this->_em->flush();

        //we have a forth revision where Owner with title changed#2 has one owned2 and two owned1 entities (created#3, created#4)
        $owner->setTitle('changed#5');

        $this->_em->flush();
        //we have a fifth revision where Owner with title changed#5 has one owned2 and two owned1 entities (created#3, created#4)

        $owner->setTitle('changed#6');
        $owned12->setTitle('changed#6');

        $this->_em->flush();

        $this->_em->remove($owned11);
        $owned12->setTitle('changed#7');
        $owner->setTitle('changed#7');
        $this->_em->flush();
        //we have a seventh revision where Owner with title changed#7 has one owned2 and one owned1 entity (changed#7)

        //checking third revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 3);
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $audited->getOwned2());
        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertCount(1, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $o1 =  $audited->getOwned1();
        $this->assertEquals('created#3', $o1[0]->getTitle());
        $o2 = $audited->getOwned2();
        $this->assertEquals('owned21', $o2[0]->getTitle());

        //checking forth revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 4);
        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $o1 = $audited->getOwned1();
        $this->assertEquals('created#3', $o1[0]->getTitle());
        $this->assertEquals('created#4', $o1[1]->getTitle());
        $o2 = $audited->getOwned2();
        $this->assertEquals('owned21', $o2[0]->getTitle());

        //check skipping collections
        $auditReader->setLoadAuditedCollections(false);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 4);
        $this->assertCount(0, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());

        $auditReader->setLoadNativeCollections(false);
        $auditReader->setLoadAuditedCollections(true);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 4);
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(0, $audited->getOwned2());

        //checking fifth revision
        $auditReader->setLoadNativeCollections(true);
        $auditReader->clearEntityCache();
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 5);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $o1 = $audited->getOwned1();
        $this->assertEquals('created#3', $o1[0]->getTitle());
        $this->assertEquals('created#4', $o1[1]->getTitle());
        $o2 = $audited->getOwned2();
        $this->assertEquals('owned21', $o2[0]->getTitle());

        //checking sixth revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 6);
        $this->assertEquals('changed#6', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $o1 = $audited->getOwned1();
        $this->assertEquals('created#3', $o1[0]->getTitle());
        $this->assertEquals('changed#6', $o1[1]->getTitle());
        $o2 = $audited->getOwned2();
        $this->assertEquals('owned21', $o2[0]->getTitle());

        //checking seventh revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 7);
        $this->assertEquals('changed#7', $audited->getTitle());
        $this->assertCount(1, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $o1 = $audited->getOwned1();
        $this->assertEquals('changed#7', $o1[0]->getTitle());
        $o2 = $audited->getOwned2();
        $this->assertEquals('owned21', $o2[0]->getTitle());

        $history = $auditReader->getEntityHistory(get_class($owner), $owner->getId());

        $this->assertCount(5, $history);
    }

    /**
     * @group mysql
     */
    public function testRemoval()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

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

        $this->_em->persist($owner1);
        $this->_em->persist($owner2);
        $this->_em->persist($owned1);
        $this->_em->persist($owned2);
        $this->_em->persist($owned3);

        $this->_em->flush(); //#1

        $owned1->setOwner($owner2);
        $this->_em->flush(); //#2

        $this->_em->remove($owned1);
        $this->_em->flush(); //#3

        $owned2->setTitle('updated owned2');
        $this->_em->flush(); //#4

        $this->_em->remove($owned2);
        $this->_em->flush(); //#5

        $this->_em->remove($owned3);
        $this->_em->flush(); //#6

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 1);
        $this->assertCount(3, $owner->getOwned1());

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 2);
        $this->assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 3);
        $this->assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 4);
        $this->assertCount(2, $owner->getOwned1());

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 5);
        $this->assertCount(1, $owner->getOwned1());

        $owner = $auditReader->find(get_class($owner1), $owner1->getId(), 6);
        $this->assertCount(0, $owner->getOwned1());
    }

    /**
     * @group mysql
     */
    public function testDetaching()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $owner = new OwnerEntity();
        $owner->setTitle('created#1');

        $owned = new OwnedEntity1();
        $owned->setTitle('created#1');

        $this->_em->persist($owner);
        $this->_em->persist($owned);

        $this->_em->flush(); //#1

        $ownerId1 = $owner->getId();
        $ownedId1 = $owned->getId();

        $owned->setTitle('associated#2');
        $owned->setOwner($owner);

        $this->_em->flush(); //#2

        $owned->setTitle('deassociated#3');
        $owned->setOwner(null);

        $this->_em->flush(); //#3

        $owned->setTitle('associated#4');
        $owned->setOwner($owner);

        $this->_em->flush(); //#4

        $this->_em->remove($owned);

        $this->_em->flush(); //#5

        $owned = new OwnedEntity1();
        $owned->setTitle('recreated#6');
        $owned->setOwner($owner);

        $this->_em->persist($owned);
        $this->_em->flush(); //#6

        $ownedId2 = $owned->getId();

        $this->_em->remove($owner);
        $this->_em->flush(); //#7

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 1);
        $this->assertEquals('created#1', $auditedEntity->getTitle());
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 2);
        $o1 = $auditedEntity->getOwned1();
        $this->assertCount(1, $o1);
        $this->assertEquals($ownedId1, $o1[0]->getId());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 3);
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 4);
        $this->assertCount(1, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 5);
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 6);
        $o1 = $auditedEntity->getOwned1();
        $this->assertCount(1, $o1);
        $this->assertEquals($ownedId2, $o1[0]->getId());

        $auditedEntity = $auditReader->find(get_class($owned), $ownedId2, 7);
        $this->assertEquals(null, $auditedEntity->getOwner());
    }

    public function testOneXRelations()
    {
        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $owner = new OwnerEntity();
        $owner->setTitle('owner');

        $owned = new OwnedEntity1();
        $owned->setTitle('owned');
        $owned->setOwner($owner);

        $this->_em->persist($owner);
        $this->_em->persist($owned);

        $this->_em->flush();
        //first revision done

        $owner->setTitle('changed#2');
        $owned->setTitle('changed#2');
        $this->_em->flush();

        //checking first revision
        $audited = $auditReader->find(get_class($owned), $owner->getId(), 1);
        $this->assertEquals('owned', $audited->getTitle());
        $this->assertEquals('owner', $audited->getOwner()->getTitle());

        //checking second revision
        $audited = $auditReader->find(get_class($owned), $owner->getId(), 2);

        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertEquals('changed#2', $audited->getOwner()->getTitle());
    }

    public function testOneToManyJoinedInheritance()
    {
        $food = new FoodCategory();
        $this->_em->persist($food);

        $parmesanCheese = new CheeseProduct('Parmesan');
        $this->_em->persist($parmesanCheese);

        $cheddarCheese = new CheeseProduct('Cheddar');
        $this->_em->persist($cheddarCheese);

        $vine = new WineProduct('Champagne');
        $this->_em->persist($vine);

        $food->addProduct($parmesanCheese);
        $food->addProduct($cheddarCheese);
        $food->addProduct($vine);

        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $auditedFood = $reader->find(
            get_class($food),
            $food->getId(),
            $reader->getCurrentRevision(get_class($food), $food->getId())
        );

        $this->assertInstanceOf(get_class($food), $auditedFood);
        $this->assertCount(3, $auditedFood->getProducts());

        list($productOne, $productTwo, $productThree) = $auditedFood->getProducts()->toArray();

        $this->assertInstanceOf(get_class($parmesanCheese), $productOne);
        $this->assertInstanceOf(get_class($cheddarCheese), $productTwo);
        $this->assertInstanceOf(get_class($vine), $productThree);

        $this->assertEquals($parmesanCheese->getId(), $productOne->getId());
        $this->assertEquals($cheddarCheese->getId(), $productTwo->getId());
    }

    public function testOneToManyWithIndexBy()
    {
        $page = new Page();
        $this->_em->persist($page);

        $gbLocalization = new PageLocalization('en-GB');
        $this->_em->persist($gbLocalization);

        $usLocalization = new PageLocalization('en-US');
        $this->_em->persist($usLocalization);

        $page->addLocalization($gbLocalization);
        $page->addLocalization($usLocalization);

        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $auditedPage = $reader->find(
            get_class($page),
            $page->getId(),
            $reader->getCurrentRevision(get_class($page), $page->getId())
        );

        $this->assertNotEmpty($auditedPage->getLocalizations());

        $this->assertCount(2, $auditedPage->getLocalizations());

        $this->assertNotEmpty($auditedPage->getLocalizations()->get('en-US'));
        $this->assertNotEmpty($auditedPage->getLocalizations()->get('en-GB'));
    }

    /**
     * @group mysql
     */
    public function testOneToManyCollectionDeletedElements()
    {
        $owner = new OwnerEntity();
        $this->_em->persist($owner);

        $ownedOne = new OwnedEntity1();
        $ownedOne->setTitle('Owned#1');
        $ownedOne->setOwner($owner);
        $this->_em->persist($ownedOne);

        $ownedTwo = new OwnedEntity1();
        $ownedTwo->setTitle('Owned#2');
        $ownedTwo->setOwner($owner);
        $this->_em->persist($ownedTwo);

        $ownedThree = new OwnedEntity1();
        $ownedThree->setTitle('Owned#3');
        $ownedThree->setOwner($owner);
        $this->_em->persist($ownedThree);

        $ownedFour = new OwnedEntity1();
        $ownedFour->setTitle('Owned#4');
        $ownedFour->setOwner($owner);
        $this->_em->persist($ownedFour);

        $owner->addOwned1($ownedOne);
        $owner->addOwned1($ownedTwo);
        $owner->addOwned1($ownedThree);
        $owner->addOwned1($ownedFour);

        $owner->setTitle('Owner with four owned elements.');
        $this->_em->flush(); //#1

        $owner->setTitle('Owner with three owned elements.');
        $this->_em->remove($ownedTwo);

        $this->_em->flush(); //#2

        $owner->setTitle('Just another revision.');

        $this->_em->flush(); //#3

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $auditedOwner = $reader->find(
            get_class($owner),
            $owner->getId(),
            $reader->getCurrentRevision(get_class($owner), $owner->getId())
        );

        $this->assertCount(3, $auditedOwner->getOwned1());

        $ids = array();
        foreach ($auditedOwner->getOwned1() as $ownedElement) {
            $ids[] = $ownedElement->getId();
        }

        $this->assertTrue(in_array($ownedOne->getId(), $ids));
        $this->assertTrue(in_array($ownedThree->getId(), $ids));
        $this->assertTrue(in_array($ownedFour->getId(), $ids));
    }

    public function testOneToOneEdgeCase()
    {
        $base = new RelationOneToOneEntity();

        $referenced = new RelationFoobarEntity();
        $referenced->setFoobarField('foobar');
        $referenced->setReferencedField('referenced');

        $base->setReferencedEntity($referenced);
        $referenced->setOneToOne($base);

        $this->_em->persist($base);
        $this->_em->persist($referenced);

        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $auditedBase = $reader->find(get_class($base), $base->getId(), 1);

        $this->assertEquals('foobar', $auditedBase->getReferencedEntity()->getFoobarField());
        $this->assertEquals('referenced', $auditedBase->getReferencedEntity()->getReferencedField());
    }
}
