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

namespace SimpleThings\Tests\EntityAudit;

use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\EscapedColumnsEntity;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue111Entity;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Contact;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156ContactTelephoneNumber;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Client;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31Reve;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31User;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Organization;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Project;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87ProjectComment;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Address;
use SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Customer;

class IssueTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\EscapedColumnsEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Project',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87ProjectComment',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87AbstractProject',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Organization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Address',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Customer',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Organization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue111Entity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31User',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31Reve',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Contact',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156ContactTelephoneNumber',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Client',
    );

    protected $auditedEntities = array(
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\EscapedColumnsEntity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Project',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87ProjectComment',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87AbstractProject',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Organization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Address',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue9Customer',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue87Organization',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue111Entity',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31User',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue31Reve',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Contact',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156ContactTelephoneNumber',
        'SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue156Client',
    );

    public function testIssue31()
    {
        $reve = new Issue31Reve();
        $reve->setTitre('reve');

        $this->_em->persist($reve);
        $this->_em->flush();

        $user = new Issue31User();
        $user->setTitre('user');
        $user->setReve($reve);

        $this->_em->persist($user);
        $this->_em->remove($reve);
        $this->_em->flush();
    }

    public function testIssue111()
    {
        $this->_em->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

        $e = new Issue111Entity();
        $e->setStatus('test status');

        $this->_em->persist($e);
        $this->_em->flush($e); //#1

        $this->_em->remove($e);
        $this->_em->flush(); //#2

        $reader = $this->_auditManager->createAuditReader($this->_em);

        $ae = $reader->find('SimpleThings\Tests\EntityAudit\Fixtures\Issue\Issue111Entity', 1, 2);

        $this->assertInstanceOf('DateTime', $ae->getDeletedAt());
    }

    public function testEscapedColumns()
    {
        $e = new EscapedColumnsEntity();
        $e->setLeft(1);
        $e->setLft(2);
        $this->_em->persist($e);
        $this->_em->flush();

        $reader = $this->_auditManager->createAuditReader($this->_em);

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

        $this->_em->persist($org);
        $this->_em->persist($project);
        $this->_em->persist($comment);
        $this->_em->flush();

        $auditReader = $this->_auditManager->createAuditReader($this->_em);

        $auditedProject = $auditReader->find(get_class($project), $project->getId(), 1);

        $this->assertEquals($org->getId(), $auditedProject->getOrganisation()->getId());
        $this->assertEquals('test project', $auditedProject->getTitle());
        $this->assertEquals('some property', $auditedProject->getSomeProperty());

        $auditedComment = $auditReader->find(get_class($comment), $comment->getId(), 1);
        $this->assertEquals('test project', $auditedComment->getProject()->getTitle());

        $project->setTitle('changed project title');
        $this->_em->flush();

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

        $this->_em->persist($customer);
        $this->_em->persist($address);

        $this->_em->flush(); //#1

        $reader = $this->_auditManager->createAuditReader($this->_em);

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
        $this->_em->persist($primaryOwner);

        $secondaryOwner = new DuplicateRevisionFailureTestSecondaryOwner();
        $this->_em->persist($secondaryOwner);

        $primaryOwner->addSecondaryOwner($secondaryOwner);

        $element = new DuplicateRevisionFailureTestOwnedElement();
        $this->_em->persist($element);

        $primaryOwner->addElement($element);
        $secondaryOwner->addElement($element);

        $this->_em->flush();

        $this->_em->getUnitOfWork()->clear();

        $primaryOwner = $this->_em->find('SimpleThings\Tests\EntityAudit\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner', 1);

        $this->_em->remove($primaryOwner);
        $this->_em->flush();
    }

    public function testIssue156()
    {
        $client = new Issue156Client();

        $number = new Issue156ContactTelephoneNumber();
        $number->setNumber('0123567890');
        $client->addTelephoneNumber($number);

        $this->_em->persist($client);
        $this->_em->persist($number);
        $this->_em->flush();

        $auditReader = $this->_auditManager->createAuditReader($this->_em);
        $object = $auditReader->find(get_class($number), $number->getId(), 1);
    }
}
