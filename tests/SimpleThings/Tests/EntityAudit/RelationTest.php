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

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\ORM\Mapping as ORM;

class RelationTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\EntityAudit\Tests\OwnerEntity',
        'SimpleThings\EntityAudit\Tests\OwnedEntity1',
        'SimpleThings\EntityAudit\Tests\OwnedEntity2',
        'SimpleThings\EntityAudit\Tests\OneToOneMasterEntity',
        'SimpleThings\EntityAudit\Tests\OneToOneAuditedEntity',
        'SimpleThings\EntityAudit\Tests\OneToOneNotAuditedEntity'
    );

    protected $auditedEntities = array(
        'SimpleThings\EntityAudit\Tests\OwnerEntity',
        'SimpleThings\EntityAudit\Tests\OwnedEntity1',
        'SimpleThings\EntityAudit\Tests\OneToOneAuditedEntity',
        'SimpleThings\EntityAudit\Tests\OneToOneMasterEntity'
    );

    public function testOneToOne()
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $master = new OneToOneMasterEntity();
        $master->setTitle('master#1');

        $this->em->persist($master);
        $this->em->flush(); //#1

        $notAudited = new OneToOneNotAuditedEntity();
        $notAudited->setTitle('notaudited');

        $this->em->persist($notAudited);

        $master->setNotAudited($notAudited);

        $this->em->flush(); //#2

        $audited = new OneToOneAuditedEntity();
        $audited->setTitle('audited');
        $master->setAudited($audited);

        $this->em->persist($audited);

        $this->em->flush(); //#3

        $audited->setTitle('changed#4');

        $this->em->flush(); //#4

        $master->setTitle('changed#5');

        $this->em->flush(); //#5

        $this->em->remove($audited);

        $this->em->flush(); //#6

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

        $audited = $auditReader->find(get_class($master), $master->getId(), 5);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertEquals('changed#4', $audited->getAudited()->getTitle());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());

        $audited = $auditReader->find(get_class($master), $master->getId(), 6);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertEquals(null, $audited->getAudited());
        $this->assertEquals('notaudited', $audited->getNotAudited()->getTitle());
    }

    public function testRelations()
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        //create owner
        $owner = new OwnerEntity();
        $owner->setTitle('rev#1');

        $this->em->persist($owner);
        $this->em->flush();

        $this->assertCount(1, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        //create un-managed entity
        $owned21 = new OwnedEntity2();
        $owned21->setTitle('owned21');
        $owned21->setOwner($owner);

        $this->em->persist($owned21);
        $this->em->flush();

        //should not add a revision
        $this->assertCount(1, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        $owner->setTitle('changed#2');

        $this->em->flush();

        //should add a revision
        $this->assertCount(2, $auditReader->findRevisions(get_class($owner), $owner->getId()));

        $owned11 = new OwnedEntity1();
        $owned11->setTitle('created#3');
        $owned11->setOwner($owner);

        $this->em->persist($owned11);

        $this->em->flush();

        //should not add a revision for owner
        $this->assertCount(2, $auditReader->findRevisions(get_class($owner), $owner->getId()));
        //should add a revision for owned
        $this->assertCount(1, $auditReader->findRevisions(get_class($owned11), $owner->getId()));

        //should not mess foreign keys
        $this->assertEquals($owner->getId(), $this->em->getConnection()->fetchAll('SELECT strange_owned_id_name FROM OwnedEntity1')[0]['strange_owned_id_name']);
        $this->em->refresh($owner);
        $this->assertCount(1, $owner->getOwned1());
        $this->assertCount(1, $owner->getOwned2());

        //we have a third revision where Owner with title changed#2 has one owned2 and one owned1 entity with title created#3
        $owned12 = new OwnedEntity1();
        $owned12->setTitle('created#4');
        $owned12->setOwner($owner);

        $this->em->persist($owned12);
        $this->em->flush();

        //we have a forth revision where Owner with title changed#2 has one owned2 and two owned1 entities (created#3, created#4)
        $owner->setTitle('changed#5');

        $this->em->flush();
        //we have a fifth revision where Owner with title changed#5 has one owned2 and two owned1 entities (created#3, created#4)

        $owner->setTitle('changed#6');
        $owned12->setTitle('changed#6');

        $this->em->flush();

        $this->em->remove($owned11);
        $owned12->setTitle('changed#7');
        $owner->setTitle('changed#7');
        $this->em->flush();
        //we have a seventh revision where Owner with title changed#7 has one owned2 and one owned1 entity (changed#7)

        //checking third revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 3);
        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertCount(1, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $this->assertEquals('created#3', $audited->getOwned1()[0]->getTitle());
        $this->assertEquals('owned21', $audited->getOwned2()[0]->getTitle());

        //checking forth revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 4);
        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $this->assertEquals('created#3', $audited->getOwned1()[0]->getTitle());
        $this->assertEquals('created#4', $audited->getOwned1()[1]->getTitle());
        $this->assertEquals('owned21', $audited->getOwned2()[0]->getTitle());

        //checking fifth revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 5);
        $this->assertEquals('changed#5', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $this->assertEquals('created#3', $audited->getOwned1()[0]->getTitle());
        $this->assertEquals('created#4', $audited->getOwned1()[1]->getTitle());
        $this->assertEquals('owned21', $audited->getOwned2()[0]->getTitle());

        //checking sixth revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 6);
        $this->assertEquals('changed#6', $audited->getTitle());
        $this->assertCount(2, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $this->assertEquals('created#3', $audited->getOwned1()[0]->getTitle());
        $this->assertEquals('changed#6', $audited->getOwned1()[1]->getTitle());
        $this->assertEquals('owned21', $audited->getOwned2()[0]->getTitle());

        //checking seventh revision
        $audited = $auditReader->find(get_class($owner), $owner->getId(), 7);
        $this->assertEquals('changed#7', $audited->getTitle());
        $this->assertCount(1, $audited->getOwned1());
        $this->assertCount(1, $audited->getOwned2());
        $this->assertEquals('changed#7', $audited->getOwned1()[0]->getTitle());
        $this->assertEquals('owned21', $audited->getOwned2()[0]->getTitle());
    }

    public function testDetaching()
    {
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $owner = new OwnerEntity();
        $owner->setTitle('created#1');

        $owned = new OwnedEntity1();
        $owned->setTitle('created#1');

        $this->em->persist($owner);
        $this->em->persist($owned);

        $this->em->flush();

        $ownerId1 = $owner->getId();
        $ownedId1 = $owned->getId();

        $owned->setTitle('associated#2');
        $owned->setOwner($owner);

        $this->em->flush();

        $owned->setTitle('deassociated#3');
        $owned->setOwner(null);

        $this->em->flush();

        $owned->setTitle('associated#4');
        $owned->setOwner($owner);

        $this->em->flush();

        $this->em->remove($owned);

        $this->em->flush();

        $owned = new OwnedEntity1();
        $owned->setTitle('recreated#6');
        $owned->setOwner($owner);

        $this->em->persist($owned);
        $this->em->flush();

        $ownedId2 = $owned->getId();

        $this->em->remove($owner);
        $this->em->flush();

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 1);
        $this->assertEquals('created#1', $auditedEntity->getTitle());
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 2);
        $this->assertCount(1, $auditedEntity->getOwned1());
        $this->assertEquals($ownedId1, $auditedEntity->getOwned1()[0]->getId());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 3);
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 4);
        $this->assertCount(1, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 5);
        $this->assertCount(0, $auditedEntity->getOwned1());

        $auditedEntity = $auditReader->find(get_class($owner), $ownerId1, 6);
        $this->assertCount(1, $auditedEntity->getOwned1());
        $this->assertEquals($ownedId2, $auditedEntity->getOwned1()[0]->getId());

        $auditedEntity = $auditReader->find(get_class($owned), $ownedId2, 7);
        $this->assertEquals(null, $auditedEntity->getOwner());
    }

    public function testOneXRelations()
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
        //first revision done

        $owner->setTitle('changed#2');
        $owned->setTitle('changed#2');
        $this->em->flush();

        //checking first revision
        $audited = $auditReader->find(get_class($owned), $owner->getId(), 1);
        $this->assertEquals('owned', $audited->getTitle());
        $this->assertEquals('owner', $audited->getOwner()->getTitle());

        //checking second revision
        $audited = $auditReader->find(get_class($owned), $owner->getId(), 2);

        $this->assertEquals('changed#2', $audited->getTitle());
        $this->assertEquals('changed#2', $audited->getOwner()->getTitle());
    }
}

/** @ORM\Entity */
class OneToOneMasterEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $title;

    /** @ORM\OneToOne(targetEntity="OneToOneAuditedEntity") */
    protected $audited;

    /** @ORM\OneToOne(targetEntity="OneToOneNotAuditedEntity") */
    protected $notAudited;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getAudited()
    {
        return $this->audited;
    }

    public function setAudited($audited)
    {
        $this->audited = $audited;
    }

    public function getNotAudited()
    {
        return $this->notAudited;
    }

    public function setNotAudited($notAudited)
    {
        $this->notAudited = $notAudited;
    }
}

/** @ORM\Entity */
class OneToOneAuditedEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $title;

    /** @ORM\OneToOne(targetEntity="OneToOneMasterEntity") */
    protected $master;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getMaster()
    {
        return $this->master;
    }

    public function setMaster($master)
    {
        $this->master = $master;
    }
}

/** @ORM\Entity */
class OneToOneNotAuditedEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $title;

    /** @ORM\OneToOne(targetEntity="OneToOneMasterEntity") */
    protected $master;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getMaster()
    {
        return $this->master;
    }

    public function setMaster($master)
    {
        $this->master = $master;
    }
}

/** @ORM\Entity */
class OwnerEntity
{
    /** @ORM\Id @ORM\Column(type="integer", name="some_strange_key_name") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string", name="crazy_title_to_mess_up_audit") */
    protected $title;

    /** @ORM\OneToMany(targetEntity="OwnedEntity1", mappedBy="owner") */
    protected $owned1;

    /** @ORM\OneToMany(targetEntity="OwnedEntity2", mappedBy="owner") */
    protected $owned2;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getOwned1()
    {
        return $this->owned1;
    }

    public function addOwned1($owned1)
    {
        $this->owned1[] = $owned1;
    }

    public function getOwned2()
    {
        return $this->owned2;
    }

    public function addOwned2($owned2)
    {
        $this->owned2[] = $owned2;
    }
}

/** @ORM\Entity */
class OwnedEntity1
{
    /** @ORM\Id @ORM\Column(type="integer", name="strange_owned_id_name") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string", name="even_strangier_column_name") */
    protected $title;

    /** @ORM\ManyToOne(targetEntity="OwnerEntity") @ORM\JoinColumn(name="owner_id_goes_here", referencedColumnName="some_strange_key_name") */
    protected $owner;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}

/** @ORM\Entity */
class OwnedEntity2
{
    /** @ORM\Id @ORM\Column(type="integer", name="strange_owned_id_name") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string", name="even_strangier_column_name") */
    protected $title;

    /** @ORM\ManyToOne(targetEntity="OwnerEntity") @ORM\JoinColumn(name="owner_id_goes_here", referencedColumnName="some_strange_key_name")*/
    protected $owner;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}
