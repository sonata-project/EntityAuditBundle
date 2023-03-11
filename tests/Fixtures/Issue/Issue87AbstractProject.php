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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_project_abstract')]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: Types::STRING)]
#[ORM\DiscriminatorMap(['project' => Issue87Project::class])]
abstract class Issue87AbstractProject
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 50)]
    private ?string $title = null; // This property is in the _audit table for each subclass

    #[ORM\Column(name: 'description', type: Types::STRING, length: 1000, nullable: true)]
    private ?string $description = null; // This property is in the _audit table for each subclass

    #[ORM\ManyToOne(targetEntity: Issue87Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Issue87Organization $organisation = null; // This association is NOT in the _audit table for the subclasses

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getOrganisation(): ?Issue87Organization
    {
        return $this->organisation;
    }

    public function setOrganisation(Issue87Organization $organisation): void
    {
        $this->organisation = $organisation;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
