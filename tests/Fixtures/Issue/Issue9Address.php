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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue9Address
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
     * @ORM\Column
     */
    private ?string $addressText = null;

    /**
     * @ORM\ManyToOne(targetEntity="Issue9Customer", inversedBy="addresses")
     */
    private ?Issue9Customer $customer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddressText(): ?string
    {
        return $this->addressText;
    }

    public function setAddressText(?string $addressText): void
    {
        $this->addressText = $addressText;
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
