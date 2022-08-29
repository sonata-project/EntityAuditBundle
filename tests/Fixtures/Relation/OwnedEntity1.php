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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OwnedEntity1
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
     * @ORM\ManyToOne(targetEntity="OwnerEntity")
     * @ORM\JoinColumn(name="owner_id_goes_here", referencedColumnName="some_strange_key_name", onDelete="SET NULL")
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
