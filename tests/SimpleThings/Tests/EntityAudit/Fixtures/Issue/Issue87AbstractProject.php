<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Table(name="project_project_abstract")
 * @ORM\Entity()
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
