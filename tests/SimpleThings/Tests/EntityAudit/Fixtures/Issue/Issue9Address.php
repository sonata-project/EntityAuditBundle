<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Issue9Address
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\Column
     */
    protected $address_text;

    /**
     * @ORM\ManyToOne(targetEntity="Issue9Customer", inversedBy="addresses")
     */
    protected $customer;

    public function getId()
    {
        return $this->id;
    }

    public function getAddressText()
    {
        return $this->address_text;
    }

    public function setAddressText($address_text)
    {
        $this->address_text = $address_text;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }
}
