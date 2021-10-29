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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OwnerEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="some_strange_key_name")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", name="crazy_title_to_mess_up_audit")
     */
    protected $title;

    /**
     * @ORM\OneToMany(targetEntity="OwnedEntity1", mappedBy="owner")
     */
    protected $owned1;

    /**
     * @ORM\OneToMany(targetEntity="OwnedEntity2", mappedBy="owner")
     */
    protected $owned2;

    /**
     * @ORM\ManyToMany(targetEntity="OwnedEntity3", mappedBy="owner")
     * @ORM\JoinTable(name="owner_owned3",
     *   joinColumns={@ORM\JoinColumn(name="owned3_id", referencedColumnName="strange_owned_id_name")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="some_strange_key_name")}
     * )
     */
    protected $owned3;

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

    public function getOwned1()
    {
        return $this->owned1;
    }

    public function addOwned1(OwnedEntity1 $owned1): void
    {
        $this->owned1[] = $owned1;
    }

    public function getOwned2()
    {
        return $this->owned2;
    }

    public function addOwned2(OwnedEntity2 $owned2): void
    {
        $this->owned2[] = $owned2;
    }

    public function getOwned3()
    {
        return $this->owned3;
    }

    public function addOwned3(OwnedEntity3 $owned3): void
    {
        $this->owned3[] = $owned3;
    }
}
