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
 */
class OwnedEntity4
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="strange_owned_id_name")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="even_strangier_column_name")
     */
    protected $title;

    /**
     * @var Collection<int, OwnerEntity>
     *
     * @ORM\ManyToMany(targetEntity="OwnerEntity", inversedBy="ownedInverse")
     * @ORM\JoinTable(name="owner_owned4",
     *   joinColumns={@ORM\JoinColumn(name="owned4_id", referencedColumnName="strange_owned_id_name")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="some_strange_key_name")}
     * )
     */
    protected Collection $owners;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
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
     * @psalm-return Collection<int, OwnerEntity>
     */
    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(OwnerEntity $owner): void
    {
        if (false === $this->owners->contains($owner)) {
            $this->owners->add($owner);
        }
    }
}
