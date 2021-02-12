<?php

declare(strict_types=1);

namespace SimpleThings\EntityAudit\Tests\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

class ConvertToPHPType extends TextType
{
    public function getName()
    {
        return 'upper';
    }

    public function canRequireSQLConversion()
    {
        return true;
    }

    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return sprintf('UPPER(%s)', $sqlExpr);
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('LOWER(%s)', $sqlExpr);
    }
}
