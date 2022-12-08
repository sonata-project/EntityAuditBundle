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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue9Customer
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection<int, Issue9Address>
     *
     * @ORM\OneToMany(targetEntity="Issue9Address", mappedBy="customer")
     */
    private Collection $addresses;

    /**
     * @ORM\OneToOne(targetEntity="Issue9Address")
     */
    private ?Issue9Address $primaryAddress = null;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Issue9Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * @param Collection<int, Issue9Address> $addresses
     */
    public function setAddresses($addresses): void
    {
        $this->addresses = $addresses;
    }

    public function getPrimaryAddress(): ?Issue9Address
    {
        return $this->primaryAddress;
    }

    public function setPrimaryAddress(Issue9Address $primaryAddress): void
    {
        $this->primaryAddress = $primaryAddress;
    }
}
