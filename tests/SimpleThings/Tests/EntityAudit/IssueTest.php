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

use Doctrine\ORM\Mapping as ORM;

class IssueTest extends BaseTest
{
    protected $schemaEntities = array(
        'SimpleThings\EntityAudit\Tests\EscapedColumnsEntity',
        'SimpleThings\EntityAudit\Tests\Issue87Project',
        'SimpleThings\EntityAudit\Tests\Issue87ProjectComment',
        'SimpleThings\EntityAudit\Tests\Issue87AbstractProject',
        'SimpleThings\EntityAudit\Tests\Issue87Organization'
    );

    protected $auditedEntities = array(
        'SimpleThings\EntityAudit\Tests\EscapedColumnsEntity',
        'SimpleThings\EntityAudit\Tests\Issue87Project',
        'SimpleThings\EntityAudit\Tests\Issue87ProjectComment',
        'SimpleThings\EntityAudit\Tests\Issue87AbstractProject',
        'SimpleThings\EntityAudit\Tests\Issue87Organization'
    );

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