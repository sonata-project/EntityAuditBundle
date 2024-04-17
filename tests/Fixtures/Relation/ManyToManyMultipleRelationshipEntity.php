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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ManyToManyMultipleRelationshipEntity
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING)]
    protected $title;

    /**
     * @var Collection<int, ManyToManyMultipleTargetEntity>
     */
    #[ORM\ManyToMany(targetEntity: ManyToManyMultipleTargetEntity::class)]
    #[ORM\JoinTable(name: 'many_to_many_primary_target')]
    protected $primaryTargets;

    /**
     * @var Collection<int, ManyToManyMultipleTargetEntity>
     */
    #[ORM\ManyToMany(targetEntity: ManyToManyMultipleTargetEntity::class)]
    #[ORM\JoinTable(name: 'many_to_many_secondary_target')]
    protected $secondaryTargets;

    public function __construct()
    {
        $this->primaryTargets = new ArrayCollection();
        $this->secondaryTargets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Collection<int, ManyToManyMultipleTargetEntity>
     */
    public function getPrimaryTargets(): Collection
    {
        return $this->primaryTargets;
    }

    public function addPrimaryTarget(ManyToManyMultipleTargetEntity $target): void
    {
        $this->primaryTargets[] = $target;
    }

    /**
     * @return Collection<int, ManyToManyMultipleTargetEntity>
     */
    public function getSecondaryTargets(): Collection
    {
        return $this->secondaryTargets;
    }

    public function addSecondaryTarget(ManyToManyMultipleTargetEntity $target): void
    {
        $this->secondaryTargets[] = $target;
    }
}
