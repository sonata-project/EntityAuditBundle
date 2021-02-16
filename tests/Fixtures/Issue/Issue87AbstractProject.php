<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="project_project_abstract")
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"project" = "Issue87Project"})
 */
abstract class Issue87AbstractProject
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="title", type="string", length=50)
     */
    private $title; //This property is in the _audit table for each subclass

    /**
     * @ORM\Column(name="description", type="string", length=1000, nullable=true)
     */
    private $description; //This property is in the _audit table for each subclass

    /**
     * @ORM\ManyToOne(targetEntity="Issue87Organization")
     * @ORM\JoinColumn(nullable=true)
     */
    private $organisation; //This association is NOT in the _audit table for the subclasses

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function getOrganisation(): ?Issue87Organization
    {
        return $this->organisation;
    }

    public function setOrganisation($organisation): void
    {
        $this->organisation = $organisation;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }
}
