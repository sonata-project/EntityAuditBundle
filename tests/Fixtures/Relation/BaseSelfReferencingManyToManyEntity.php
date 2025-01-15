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
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: Types::STRING)]
#[ORM\DiscriminatorMap(['type 1' => SelfReferencingManyToManyEntity::class])]
abstract class BaseSelfReferencingManyToManyEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @var Collection<int, BaseSelfReferencingManyToManyEntity>
     */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'self_referencing_linked_table')]
    #[ORM\JoinColumn(name: 'foo_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'bar_id', referencedColumnName: 'id')]
    private Collection $linkedEntities;

    public function __construct(
        #[ORM\Column(type: Types::STRING, name: 'title')]
        private string $title,
    ) {
        $this->linkedEntities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function addLinkedEntity(self $selfReferencingManyToManyEntity): self
    {
        if (!$this->linkedEntities->contains($selfReferencingManyToManyEntity)) {
            $this->linkedEntities->add($selfReferencingManyToManyEntity);
        }

        return $this;
    }
}
