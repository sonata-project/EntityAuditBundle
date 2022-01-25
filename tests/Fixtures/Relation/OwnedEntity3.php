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
class OwnedEntity3
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
     * @var OwnerEntity|null
     *
     * @ORM\ManyToMany(targetEntity="OwnerEntity", inversedBy="owned3")
     * @ORM\JoinTable(
     *     name="owner_owned3",
     *     joinColumns={@ORM\JoinColumn(name="owned3_id", referencedColumnName="strange_owned_id_name")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="some_strange_key_name")}
     * )
     */
    protected $owner;

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

    public function getOwner(): ?OwnerEntity
    {
        return $this->owner;
    }

    public function setOwner(?OwnerEntity $owner): void
    {
        $this->owner = $owner;
    }
}
