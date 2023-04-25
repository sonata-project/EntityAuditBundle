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
class OwnerEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, name: 'some_strange_key_name')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, name: 'crazy_title_to_mess_up_audit')]
    protected ?string $title = null;

    /**
     * @var Collection<int, OwnedEntity1>
     */
    #[ORM\OneToMany(targetEntity: OwnedEntity1::class, mappedBy: 'owner')]
    protected Collection $owned1;

    /**
     * @var Collection<int, OwnedEntity2>
     */
    #[ORM\OneToMany(targetEntity: OwnedEntity2::class, mappedBy: 'owner')]
    protected Collection $owned2;

    /**
     * @var Collection<int, OwnedEntity3>
     */
    #[ORM\ManyToMany(targetEntity: OwnedEntity3::class, inversedBy: 'owner')]
    #[ORM\JoinTable(name: 'owner_owned3')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'some_strange_key_name')]
    #[ORM\InverseJoinColumn(name: 'owned3_id', referencedColumnName: 'strange_owned_id_name')]
    protected Collection $owned3;

    /**
     * @var Collection<int, OwnedEntity4>
     */
    #[ORM\ManyToMany(targetEntity: OwnedEntity4::class, mappedBy: 'owners')]
    protected Collection $ownedInverse;

    public function __construct()
    {
        $this->owned1 = new ArrayCollection();
        $this->owned2 = new ArrayCollection();
        $this->owned3 = new ArrayCollection();
        $this->ownedInverse = new ArrayCollection();
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
     * @return Collection<int, OwnedEntity1>
     */
    public function getOwned1()
    {
        return $this->owned1;
    }

    public function addOwned1(OwnedEntity1 $owned1): void
    {
        $this->owned1[] = $owned1;
    }

    /**
     * @return Collection<int, OwnedEntity2>
     */
    public function getOwned2()
    {
        return $this->owned2;
    }

    public function addOwned2(OwnedEntity2 $owned2): void
    {
        $this->owned2[] = $owned2;
    }

    /**
     * @return Collection<int, OwnedEntity3>
     */
    public function getOwned3(): Collection
    {
        return $this->owned3;
    }

    public function addOwned3(OwnedEntity3 $owned3): void
    {
        $this->owned3[] = $owned3;
    }

    /**
     * @return Collection<int, OwnedEntity4>
     */
    public function getOwnedInverse(): Collection
    {
        return $this->ownedInverse;
    }

    public function addOwnedInverse(OwnedEntity4 $ownedInverse): void
    {
        $this->ownedInverse[] = $ownedInverse;
    }
}
