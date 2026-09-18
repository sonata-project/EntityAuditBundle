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
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\Table;

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

    /**
     * Returns the unquoted name of a table, usable as a base for deriving a related
     * table name through concatenation.
     *
     * On DBAL >= 4.3, Table::getName() is deprecated and Table::getObjectName()->toString()
     * renders a *quoted* identifier for reserved keywords (e.g. `"user"`). Concatenating
     * that with a prefix/suffix yields an unparseable name (`"user"_audit`), which DBAL
     * leaves uninitialized and then rejects from getObjectName(). Using the raw identifier
     * value preserves the historical (pre-4.3) behaviour.
     */
    protected function getUnquotedTableName(Table $table): string
    {
        if ($this->isDbal4_3()) {
            $name = $table->getObjectName();
            $qualifier = $name->getQualifier();

            return (null !== $qualifier ? $qualifier->getValue().'.' : '').$name->getUnqualifiedName()->getValue();
        }

        return $table->getName(); // @phpstan-ignore-line
    }

    protected function isDbal4(): bool
    {
        return is_subclass_of(ParameterType::class, \UnitEnum::class); // @phpstan-ignore-line class changed to enum in doctrine/dbal 4
    }
}
