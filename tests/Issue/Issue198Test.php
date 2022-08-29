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

use Sonata\EntityAuditBundle\Tests\BaseTest;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue198Car;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue198Owner;

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

        $carId = $car->getId();
        static::assertNotNull($carId);

        $car1 = $auditReader->find(Issue198Car::class, $carId, 1);
        static::assertNotNull($car1);
        static::assertNull($car1->getOwner());

        $car2 = $auditReader->find(Issue198Car::class, $carId, 2);
        static::assertNotNull($car2);
        $car2Owner = $car2->getOwner();
        static::assertNotNull($car2Owner);
        static::assertSame($car2Owner->getId(), $owner->getId());
    }
}
