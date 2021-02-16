<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Issue9Address
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

    public function getId(): int
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

    public function setCustomer($customer): void
    {
        $this->customer = $customer;
    }
}
