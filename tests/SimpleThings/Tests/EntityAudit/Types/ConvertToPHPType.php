<?php

namespace SimpleThings\EntityAudit\Tests\Types;

use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

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
