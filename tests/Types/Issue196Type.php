<?php

namespace SimpleThings\EntityAudit\Tests\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * Extension of Doctrine's TextType that forces values to lower case when persisting.
 */
class Issue196Type extends TextType
{
    public function getName()
    {
        return 'issue196type';
    }

    public function canRequireSQLConversion()
    {
        return true;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('lower(%s)', $sqlExpr);
    }
}
