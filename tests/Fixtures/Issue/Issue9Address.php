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
class Issue9Address
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column
     */
    private $address_text;

    /**
     * @ORM\ManyToOne(targetEntity="Issue9Customer", inversedBy="addresses")
     */
    private $customer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddressText()
    {
        return $this->address_text;
    }

    public function setAddressText($address_text): void
    {
        $this->address_text = $address_text;
    }

    public function getCustomer(): ?Issue9Customer
    {
        return $this->customer;
    }

    public function setCustomer(Issue9Customer $customer): void
    {
        $this->customer = $customer;
    }
}
