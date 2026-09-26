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

namespace SimpleThings\EntityAudit\Utils;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Name;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\NamedObject;

/**
 * NEXT_MAJOR: remove this trait and all `if` blocks.
 *
 * @internal
 */
trait DbalCompatibilityTrait
{
    protected function isDbal4_3(): bool
    {
        return class_exists(UnqualifiedName::class); // @phpstan-ignore-line class added in doctrine/dbal 4.3
    }

    protected function isDbal4(): bool
    {
        return is_subclass_of(ParameterType::class, \UnitEnum::class); // @phpstan-ignore-line class changed to enum in doctrine/dbal 4
    }

    /**
     * Reads the name through the public NamedObject interface: AbstractNamedObject,
     * which Table and Column extend, is internal since doctrine/dbal 4.5.
     *
     * @param NamedObject<covariant Name> $object
     */
    protected function getObjectNameString(NamedObject $object): string
    {
        return $object->getObjectName()->toString();
    }
}
