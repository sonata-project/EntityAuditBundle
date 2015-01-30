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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

class IssueTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\EntityAudit\Tests\EscapedColumnsEntity',
        'SimpleThings\EntityAudit\Tests\Issue87Project',
        'SimpleThings\EntityAudit\Tests\Issue87ProjectComment',
        'SimpleThings\EntityAudit\Tests\Issue87AbstractProject',
        'SimpleThings\EntityAudit\Tests\Issue87Organization',
        'SimpleThings\EntityAudit\Tests\Issue9Address',
        'SimpleThings\EntityAudit\Tests\Issue9Customer',
        'SimpleThings\EntityAudit\Tests\Issue87Organization',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestPrimaryOwner',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestSecondaryOwner',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestOwnedElement',
        'SimpleThings\EntityAudit\Tests\Issue111Entity',
        'SimpleThings\EntityAudit\Tests\Issue31User',
        'SimpleThings\EntityAudit\Tests\Issue31Reve',
    );

    protected $auditedEntities = array(
        'SimpleThings\EntityAudit\Tests\EscapedColumnsEntity',
        'SimpleThings\EntityAudit\Tests\Issue87Project',
        'SimpleThings\EntityAudit\Tests\Issue87ProjectComment',
        'SimpleThings\EntityAudit\Tests\Issue87AbstractProject',
        'SimpleThings\EntityAudit\Tests\Issue87Organization',
        'SimpleThings\EntityAudit\Tests\Issue9Address',
        'SimpleThings\EntityAudit\Tests\Issue9Customer',
        'SimpleThings\EntityAudit\Tests\Issue87Organization',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestPrimaryOwner',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestSecondaryOwner',
        'SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestOwnedElement',
        'SimpleThings\EntityAudit\Tests\Issue111Entity',
        'SimpleThings\EntityAudit\Tests\Issue31User',
        'SimpleThings\EntityAudit\Tests\Issue31Reve',
    );

    protected function getGedmoVersion()
    {
        if (class_exists('Gedmo\Version')) {
            return constant('Gedmo\Version::VERSION');
        } elseif (class_exists('Gedmo\DoctrineExtensions')) {
            return constant('Gedmo\DoctrineExtensions::VERSION');
        } else {
            return '0.0.1-DEV';
        }
    }

    public function setUp()
    {
        //softdeleteable is present only in gedmo's 2.3+
        if (version_compare($this->getGedmoVersion(), '2.3') < 0) {
            $this->auditedEntities = array_diff($this->auditedEntities, array('SimpleThings\EntityAudit\Tests\Issue111Entity'));
            $this->schemaEntities = array_diff($this->schemaEntities, array('SimpleThings\EntityAudit\Tests\Issue111Entity'));
        }

        parent::setUp();
    }

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
        $this->em->remove($reve);
        $this->em->flush();
    }

    public function testIssue111()
    {
        if (version_compare($this->getGedmoVersion(), '2.3') < 0) {
            $this->markTestSkipped('SoftDeleteable is available only since gedmo 2.3');
        }

        $this->em->getEventManager()->addEventSubscriber(new \Gedmo\SoftDeleteable\SoftDeleteableListener());

        $e = new Issue111Entity();
        $e->setStatus('test status');

        $this->em->persist($e);
        $this->em->flush($e); //#1

        $this->em->remove($e);
        $this->em->flush(); //#2

        $reader = $this->auditManager->createAuditReader($this->em);

        $ae = $reader->find('SimpleThings\EntityAudit\Tests\Issue111Entity', 1, 2);

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

        $aCustomer = $reader->find(get_class($customer), $customer->getId(), 1);
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

        $primaryOwner = $this->em->find('SimpleThings\EntityAudit\Tests\DuplicateRevisionFailureTestPrimaryOwner', 1);

        $this->em->remove($primaryOwner);
        $this->em->flush();
    }
}

/** @ORM\Entity */
class Issue31User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31Reve", cascade={"persist", "remove"})
     */
    protected $reve;

    /** @ORM\Column(type="string") */
    protected $titre;

    public function getId()
    {
        return $this->id;
    }

    public function getReve()
    {
        return $this->reve;
    }

    public function setReve($reve)
    {
        $this->reve = $reve;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }
}

/** @ORM\Entity */
class Issue31Reve
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31User")
     */
    protected $user;

    /** @ORM\Column(type="string") */
    protected $titre;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }
    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }
}

/** @ORM\Entity  @Gedmo\SoftDeleteable(fieldName="deletedAt") */
class Issue111Entity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column */
    protected $status;

    /** @ORM\Column(type="datetime", nullable=true, name="deleted_at") */
    protected $deletedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}

/**
 * @ORM\Entity
 */
class Issue9Address
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\Column
     */
    protected $address_text;

    /**
     * @ORM\ManyToOne(targetEntity="Issue9Customer", inversedBy="addresses")
     */
    protected $customer;

    public function getId()
    {
        return $this->id;
    }

    public function getAddressText()
    {
        return $this->address_text;
    }

    public function setAddressText($address_text)
    {
        $this->address_text = $address_text;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }
}

/**
 * @ORM\Entity
 */
class Issue9Customer
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Issue9Address", mappedBy="customer")
     */
    protected $addresses;

    /**
     * @ORM\OneToOne(targetEntity="Issue9Address")
     */
    protected $primary_address;

    public function getId()
    {
        return $this->id;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }

    public function getPrimaryAddress()
    {
        return $this->primary_address;
    }

    public function setPrimaryAddress($primary_address)
    {
        $this->primary_address = $primary_address;
    }
}

/**
 * @ORM\Entity
 */
class Issue87ProjectComment
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\ManytoOne(targetEntity="Issue87AbstractProject") @ORM\JoinColumn(name="a_join_column") */
    protected $project;

    /** @ORM\Column(type="text") */
    protected $text;

    public function getId()
    {
        return $this->id;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function setProject($project)
    {
        $this->project = $project;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }
}

/**
 * @ORM\Table(name="project_project_abstract")
 * @ORM\Entity(repositoryClass="Umm\ProjectBundle\Repository\AbstractProjectRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"project" = "Issue87Project"})
 */
abstract class Issue87AbstractProject
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(name="title", type="string", length=50) */
    protected $title; //This property is in the _audit table for each subclass

    /** @ORM\Column(name="description", type="string", length=1000, nullable=true) */
    protected $description; //This property is in the _audit table for each subclass

    /**
     * @ORM\ManyToOne(targetEntity="Issue87Organization")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $organisation; //This association is NOT in the _audit table for the subclasses

    public function getId()
    {
        return $this->id;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getOrganisation()
    {
        return $this->organisation;
    }

    public function setOrganisation($organisation)
    {
        $this->organisation = $organisation;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }
}

/** @ORM\Entity @ORM\Table(name="project_project") */
class Issue87Project extends Issue87AbstractProject
{
    /**
     * @ORM\Column(type="string")
     */
    protected $someProperty;

    public function getSomeProperty()
    {
        return $this->someProperty;
    }

    public function setSomeProperty($someProperty)
    {
        $this->someProperty = $someProperty;
    }
}

/** @ORM\Entity */
class Issue87Organization
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

/** @ORM\Entity */
class EscapedColumnsEntity
{
    /** @ORM\Id @ORM\GeneratedValue() @ORM\Column(type="integer") */
    protected $id;

    /** @ORM\Column(type="integer", name="lft") */
    protected $left;

    /** @ORM\Column(type="integer", name="`left`") */
    protected $lft;

    public function getId()
    {
        return $this->id;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setLeft($left)
    {
        $this->left = $left;
    }

    public function getLft()
    {
        return $this->lft;
    }

    public function setLft($lft)
    {
        $this->lft = $lft;
    }
}

/**
 * @ORM\MappedSuperclass
 */
abstract class DuplicateRevisionFailureTestEntity
{
    /** @ORM\Id @ORM\GeneratedValue() @ORM\Column(type="integer") */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * NB! Object property order matters!
 *
 * @ORM\Entity
 */
class DuplicateRevisionFailureTestPrimaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\OneToMany(targetEntity="DuplicateRevisionFailureTestOwnedElement", mappedBy="primaryOwner", cascade={"persist", "remove"}, fetch="LAZY")
     */
    protected $elements;

    /**
     * @ORM\OneToMany(targetEntity="DuplicateRevisionFailureTestSecondaryOwner", mappedBy="primaryOwner", cascade={"persist", "remove"})
     */
    protected $secondaryOwners;

    public function __construct()
    {
        $this->secondaryOwners = new ArrayCollection();
        $this->elements = new ArrayCollection();
    }

    public function addSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $secondaryOwner)
    {
        $secondaryOwner->setPrimaryOwner($this);
        $this->secondaryOwners->add($secondaryOwner);
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element)
    {
        $element->setPrimaryOwner($this);
        $this->elements->add($element);
    }
}


/** @ORM\Entity */
class DuplicateRevisionFailureTestSecondaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestPrimaryOwner", inversedBy="secondaryOwners")
     */
    protected $primaryOwner;

    /**
     * @ORM\OneToMany(targetEntity="DuplicateRevisionFailureTestOwnedElement", mappedBy="secondaryOwner", cascade={"persist", "remove"})
     */
    protected $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner)
    {
        $this->primaryOwner = $owner;
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element)
    {
        $element->setSecondaryOwner($this);
        $this->elements->add($element);
    }
}

/** @ORM\Entity */
class DuplicateRevisionFailureTestOwnedElement extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestPrimaryOwner", inversedBy="elements")
     */
    protected $primaryOwner;

    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestSecondaryOwner", inversedBy="elements")
     */
    protected $secondaryOwner;

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner)
    {
        $this->primaryOwner = $owner;
    }

    public function setSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $owner)
    {
        $this->secondaryOwner = $owner;
    }
}
