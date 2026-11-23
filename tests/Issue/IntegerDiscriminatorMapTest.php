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

use Sonata\EntityAuditBundle\Tests\BaseTestCase;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\IntegerDiscriminatorBike;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\IntegerDiscriminatorCar;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\IntegerDiscriminatorVehicle;

final class IntegerDiscriminatorMapTest extends BaseTestCase
{
    protected $schemaEntities = [
        IntegerDiscriminatorVehicle::class,
        IntegerDiscriminatorBike::class,
        IntegerDiscriminatorCar::class,
    ];

    protected $auditedEntities = [
        IntegerDiscriminatorVehicle::class,
        IntegerDiscriminatorBike::class,
        IntegerDiscriminatorCar::class,
    ];

    public function testFindSubclassWithIntegerDiscriminator(): void
    {
        $em = $this->getEntityManager();

        $bike = new IntegerDiscriminatorBike('tourer');
        $car = new IntegerDiscriminatorCar('saloon', 'red');

        $em->persist($bike);
        $em->persist($car);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $bikeId = $bike->getId();
        $carId = $car->getId();
        static::assertNotNull($bikeId);
        static::assertNotNull($carId);

        $auditedBike = $reader->find(IntegerDiscriminatorBike::class, $bikeId, 1);
        $auditedCar = $reader->find(IntegerDiscriminatorCar::class, $carId, 1);

        static::assertInstanceOf(IntegerDiscriminatorBike::class, $auditedBike);
        static::assertSame('tourer', $auditedBike->getName());
        static::assertInstanceOf(IntegerDiscriminatorCar::class, $auditedCar);
        static::assertSame('saloon', $auditedCar->getName());
        static::assertSame('red', $auditedCar->getColor());
    }
}
