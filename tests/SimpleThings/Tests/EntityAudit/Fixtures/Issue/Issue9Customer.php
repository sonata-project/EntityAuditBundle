<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Issue9Customer
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Issue9Address", mappedBy="customer")
     */
    protected $addresses;

    /**
     * @ORM\OneToOne(targetEntity="Issue9Address")
     */
    protected $primary_address;

    public function getId()
    {
        return $this->id;
    }

    public function getAddresses()
    {
        return $this->addresses;
    }

    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
    }

    /**
     * @return Issue9Address
     */
    public function getPrimaryAddress()
    {
        return $this->primary_address;
    }

    public function setPrimaryAddress($primary_address)
    {
        $this->primary_address = $primary_address;
    }
}
