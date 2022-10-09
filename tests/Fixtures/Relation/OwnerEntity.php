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
class OwnerEntity
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="some_strange_key_name")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="crazy_title_to_mess_up_audit")
     */
    protected $title;

    /**
    @var Collection<int, OwnedEntity1>
     *
     * @ORM\OneToMany(targetEntity="OwnedEntity1", mappedBy="owner")
     */
    protected $owned1;

    /**
     * @var Collection<int, OwnedEntity2>
     *
     * @ORM\OneToMany(targetEntity="OwnedEntity2", mappedBy="owner")
     */
    protected $owned2;

    /**
     * @var Collection<int, OwnedEntity3>
     *
     * @ORM\ManyToMany(targetEntity="OwnedEntity3", inversedBy="owner")
     * @ORM\JoinTable(name="owner_owned3",
     *   joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="some_strange_key_name")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="owned3_id", referencedColumnName="strange_owned_id_name")}
     * )
     */
    protected $owned3;

    /**
     * @var Collection<int, OwnedEntity4>
     *
     * @ORM\ManyToMany(targetEntity="OwnedEntity4", mappedBy="owners")
     */
    protected $ownedInverse;

    public function __construct()
    {
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
