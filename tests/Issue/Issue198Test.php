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

use SimpleThings\EntityAudit\Tests\BaseTest;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Car;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue198Owner;

final class Issue198Test extends BaseTest
{
    protected $schemaEntities = [
        Issue198Car::class,
        Issue198Owner::class,
    ];

    protected $auditedEntities = [
        Issue198Car::class,
        Issue198Owner::class,
    ];

    public function testIssue198(): void
    {
        $owner = new Issue198Owner();
        $car = new Issue198Car();

        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();

        $owner->addCar($car);

        $this->em->persist($owner);
        $this->em->persist($car);
        $this->em->flush();

        $auditReader = $this->auditManager->createAuditReader($this->em);

        $car1 = $auditReader->find(\get_class($car), $car->getId(), 1);
        static::assertNull($car1->getOwner());

        $car2 = $auditReader->find(\get_class($car), $car->getId(), 2);
        static::assertSame($car2->getOwner()->getId(), $owner->getId());
    }
}
