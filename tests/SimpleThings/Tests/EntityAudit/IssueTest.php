<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
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

use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\EscapedColumnsEntity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\ConvertToPHPEntity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue111Entity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156Contact;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156ContactTelephoneNumber;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue156Client;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue194User;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue194Address;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue196Entity;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue31Reve;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue31User;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87Organization;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87Project;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue87ProjectComment;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Address;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Customer;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Owner;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Car;

class IssueTest extends BaseTest
{
    protected $fixturesPath = __DIR__ . '/Fixtures/Issue';

    protected $customTypes = array(
        'issue196type' => 'SimpleThings\EntityAudit\Tests\Types\Issue196Type',
        'upper' => 'SimpleThings\EntityAudit\Tests\Types\ConvertToPHPType',
    );

    public function testIssue31()
    {
        $reve = new Issue31Reve();
        $reve->setTitre('reve');

        $this->em->persist($reve);
        $this->em->flush();

        $user = new Issue31User();
        $user->setTitre('user');
        $user->setReve($reve);

        $this->em->persist($user);
        $this->em->flush();

        $this->em->remove($user);
        $this->em->flush();
    }

    public function testIssue111()
    {
        $this->em->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

        $e = new Issue111Entity();
        $e->setStatus('test status');

        $this->em->persist($e);
        $this->em->flush($e); //#1

        $this->em->remove($e);
        $this->em->flush(); //#2

        $reader = $this->auditManager->createAuditReader($this->em);

        $ae = $reader->find('SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue111Entity', 1, 2);

        $this->assertInstanceOf('DateTime', $ae->getDeletedAt());
    }

    public function testEscapedColumns()
    {
        $e = new EscapedColumnsEntity();
        $e->setLeft(1);
        $e->setLft(2);
        $this->em->persist($e);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $reader->find(get_class($e), $e->getId(), 1);
    }

    public function testIssue87()
    {
        $org = new Issue87Organization();
        $project = new Issue87Project();
        $project->setOrganisation($org);
        $project->setSomeProperty('some property');
        $project->setTitle('test project');
        $comment = new Issue87ProjectComment();
        $comment->setProject($project);
        $comment->setText('text comment');

        $this->em->persist($org);
        $this->em->persist($project);
        $this->em->persist($comment);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader($this->em);

        $auditedProject = $auditReader->find(get_class($project), $project->getId(), 1);

        $this->assertEquals($org->getId(), $auditedProject->getOrganisation()->getId());
        $this->assertEquals('test project', $auditedProject->getTitle());
        $this->assertEquals('some property', $auditedProject->getSomeProperty());

        $auditedComment = $auditReader->find(get_class($comment), $comment->getId(), 1);
        $this->assertEquals('test project', $auditedComment->getProject()->getTitle());

        $project->setTitle('changed project title');
        $this->em->flush();

        $auditedComment = $auditReader->find(get_class($comment), $comment->getId(), 2);
        $this->assertEquals('changed project title', $auditedComment->getProject()->getTitle());

    }

    public function testIssue9()
    {
        $address = new Issue9Address();
        $address->setAddressText('NY, Red Street 6');

        $customer = new Issue9Customer();
        $customer->setAddresses(array($address));
        $customer->setPrimaryAddress($address);

        $address->setCustomer($customer);

        $this->em->persist($customer);
        $this->em->persist($address);

        $this->em->flush(); //#1

        $reader = $this->auditManager->createAuditReader($this->em);

        $aAddress = $reader->find(get_class($address), $address->getId(), 1);
        $this->assertEquals($customer->getId(), $aAddress->getCustomer()->getId());

        /** @var Issue9Customer $aCustomer */
        $aCustomer = $reader->find(get_class($customer), $customer->getId(), 1);

        $this->assertNotNull($aCustomer->getPrimaryAddress());
        $this->assertEquals('NY, Red Street 6', $aCustomer->getPrimaryAddress()->getAddressText());
    }

    public function testDuplicateRevisionKeyConstraintFailure()
    {
        $primaryOwner = new DuplicateRevisionFailureTestPrimaryOwner();
        $this->em->persist($primaryOwner);

        $secondaryOwner = new DuplicateRevisionFailureTestSecondaryOwner();
        $this->em->persist($secondaryOwner);

        $primaryOwner->addSecondaryOwner($secondaryOwner);

        $element = new DuplicateRevisionFailureTestOwnedElement();
        $this->em->persist($element);

        $primaryOwner->addElement($element);
        $secondaryOwner->addElement($element);

        $this->em->flush();

        $this->em->getUnitOfWork()->clear();

        $primaryOwner = $this->em->find('SimpleThings\EntityAudit\Tests\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner', 1);

        $this->em->remove($primaryOwner);
        $this->em->flush();
    }

    public function testIssue156()
    {
        $client = new Issue156Client();

        $number = new Issue156ContactTelephoneNumber();
        $number->setNumber('0123567890');
        $client->addTelephoneNumber($number);

        $this->em->persist($client);
        $this->em->persist($number);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $object = $auditReader->find(get_class($number), $number->getId(), 1);
    }

    public function testIssue194()
    {
        $user = new Issue194User();
        $address = new Issue194Address($user);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->persist($address);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $auditUser = $auditReader->find(get_class($user), $user->getId(), 1);
        $auditAddress = $auditReader->find(get_class($address), $address->getUser()->getId(), 2);
        $this->assertEquals($auditAddress->getUser(), $auditUser);
        $this->assertEquals($address->getUser(), $auditUser);
        $this->assertEquals($auditAddress->getUser(), $user);
    }
    
    public function testIssue196()
    {
        $entity = new Issue196Entity();
        $entity->setSqlConversionField('THIS SHOULD BE LOWER CASE');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $persistedEntity = $this->em->find(get_class($entity), $entity->getId());

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $currentRevision = $auditReader->getCurrentRevision(get_class($entity), $entity->getId());
        $currentRevisionEntity = $auditReader->find(get_class($entity), $entity->getId(), $currentRevision);

        $this->assertEquals(
            $persistedEntity,
            $currentRevisionEntity,
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }

    public function testIssue198()
    {
        $owner = new Issue198Owner();
        $car = new Issue198Car();
        
        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();
        
        $owner->addCar($car);

        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader($this->em);
        
        $car1 = $auditReader->find(get_class($car), $car->getId(), 1);
        $this->assertNull($car1->getOwner());
        
        $car2 = $auditReader->find(get_class($car), $car->getId(), 2);
        $this->assertEquals($car2->getOwner()->getId(), $owner->getId());
    }

    public function testConvertToPHP()
    {
        $entity = new ConvertToPHPEntity();
        $entity->setSqlConversionField('TEST CONVERT TO PHP');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $persistedEntity = $this->em->find(get_class($entity), $entity->getId());

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $currentRevision = $auditReader->getCurrentRevision(get_class($entity), $entity->getId());
        $currentRevisionEntity = $auditReader->find(get_class($entity), $entity->getId(), $currentRevision);

        $this->assertEquals(
            $persistedEntity,
            $currentRevisionEntity,
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }
}
