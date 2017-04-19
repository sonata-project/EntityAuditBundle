<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @ORM\Entity()
 * @Auditable()
 */
class ConvertToPHPEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="upper") */
    protected $sqlConversionField;

    public function getId()
    {
        return $this->id;
    }

    public function getSqlConversionField()
    {
        return $this->sqlConversionField;
    }

    public function setSqlConversionField($sqlConversionField)
    {
        $this->sqlConversionField = $sqlConversionField;
    }
}
