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

namespace Sonata\EntityAuditBundle\Tests\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

final class ConvertToPHPType extends TextType
{
    public function getName(): string
    {
        return 'upper';
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        return \sprintf('UPPER(%s)', $sqlExpr);
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        return \sprintf('LOWER(%s)', $sqlExpr);
    }
}
