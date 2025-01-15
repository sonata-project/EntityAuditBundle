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
class UnidirectionalManyToManyEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @var Collection<int, UnidirectionalManyToManyLinkedEntity>
     */
    #[ORM\ManyToMany(targetEntity: UnidirectionalManyToManyLinkedEntity::class)]
    #[ORM\JoinTable(name: 'unidirectional_many_to_many_linked_entity')]
    #[ORM\JoinColumn(name: 'foo_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'bar_id', referencedColumnName: 'id')]
    private Collection $linkedEntity;

    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        private string $title,
    ) {
        $this->linkedEntity = new ArrayCollection();
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

    /**
     * @return Collection<int, UnidirectionalManyToManyLinkedEntity>
     */
    public function getLinkedEntities(): Collection
    {
        return $this->linkedEntity;
    }

    public function addLinkedEntity(UnidirectionalManyToManyLinkedEntity $linkedEntity): void
    {
        if (false === $this->linkedEntity->contains($linkedEntity)) {
            $this->linkedEntity->add($linkedEntity);
        }
    }
}
