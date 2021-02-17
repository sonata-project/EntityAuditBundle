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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Issue9Customer
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="Issue9Address", mappedBy="customer")
     */
    private $addresses;

    /**
     * @ORM\OneToOne(targetEntity="Issue9Address")
     */
    private $primary_address;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function setAddresses($addresses): void
    {
        $this->addresses = $addresses;
    }

    /**
     * @return Issue9Address
     */
    public function getPrimaryAddress(): ?Issue9Address
    {
        return $this->primary_address;
    }

    public function setPrimaryAddress($primary_address): void
    {
        $this->primary_address = $primary_address;
    }
}
