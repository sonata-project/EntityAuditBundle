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

namespace SimpleThings\EntityAudit\Tests\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use SimpleThings\EntityAudit\Tests\BaseTest;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Address;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue9Customer;

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

        $this->em->flush(); //#1

        $reader = $this->auditManager->createAuditReader($this->em);

        $aAddress = $reader->find(\get_class($address), $address->getId(), 1);
        static::assertSame($customer->getId(), $aAddress->getCustomer()->getId());

        /** @var Issue9Customer $aCustomer */
        $aCustomer = $reader->find(\get_class($customer), $customer->getId(), 1);

        static::assertNotNull($aCustomer->getPrimaryAddress());
        static::assertSame('NY, Red Street 6', $aCustomer->getPrimaryAddress()->getAddressText());
    }
}
