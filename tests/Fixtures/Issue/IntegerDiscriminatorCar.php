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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress ClassMustBeFinal
 */
#[ORM\Entity]
class IntegerDiscriminatorCar extends IntegerDiscriminatorVehicle
{
    public function __construct(
        string $name,
        #[ORM\Column(type: Types::STRING)]
        private string $color,
    ) {
        parent::__construct($name);
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
