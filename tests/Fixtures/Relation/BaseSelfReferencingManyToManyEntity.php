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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "type 1": "Sonata\EntityAuditBundle\Tests\Fixtures\Relation\SelfReferencingManyToManyEntity"
 * })
 */
abstract class BaseSelfReferencingManyToManyEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", name="title")
     */
    private string $title;

    /**
     * @var Collection<int, BaseSelfReferencingManyToManyEntity>
     *
     * @ORM\ManyToMany(targetEntity="BaseSelfReferencingManyToManyEntity")
     * @ORM\JoinTable(
     *     name="self_referencing_linked_table",
     *     joinColumns={@ORM\JoinColumn(name="foo_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="bar_id", referencedColumnName="id")}
     * )
     */
    private Collection $linkedEntities;

    public function __construct(string $title)
    {
        $this->title = $title;
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
