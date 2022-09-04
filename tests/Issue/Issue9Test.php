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

namespace Sonata\EntityAuditBundle\Tests\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Sonata\EntityAuditBundle\Tests\BaseTest;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue9Address;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue9Customer;

final class Issue9Test extends BaseTest
{
    protected $schemaEntities = [
        Issue9Address::class,
        Issue9Customer::class,
    ];

    protected $auditedEntities = [
        Issue9Address::class,
        Issue9Customer::class,
    ];

    public function testIssue9(): void
    {
        $address = new Issue9Address();
        $address->setAddressText('NY, Red Street 6');

        $customer = new Issue9Customer();
        $customer->setAddresses(new ArrayCollection([$address]));
        $customer->setPrimaryAddress($address);

        $address->setCustomer($customer);

        $this->em->persist($customer);
        $this->em->persist($address);

        $this->em->flush(); // #1

        $reader = $this->auditManager->createAuditReader($this->em);

        $addressId = $address->getId();
        static::assertNotNull($addressId);

        $aAddress = $reader->find(Issue9Address::class, $addressId, 1);
        static::assertNotNull($aAddress);
        $aAddressCustomer = $aAddress->getCustomer();
        static::assertNotNull($aAddressCustomer);
        static::assertSame($customer->getId(), $aAddressCustomer->getId());

        $customerId = $customer->getId();
        static::assertNotNull($customerId);

        $aCustomer = $reader->find(Issue9Customer::class, $customerId, 1);
        static::assertNotNull($aCustomer);

        $aPrimaryAddress = $aCustomer->getPrimaryAddress();
        static::assertNotNull($aPrimaryAddress);
        static::assertSame('NY, Red Street 6', $aPrimaryAddress->getAddressText());
    }
}
