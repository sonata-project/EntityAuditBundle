<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue196Entity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="issue196type") */
    protected $sqlConversionField;

    public function getId()
    {
        return $this->id;
    }

    public function setSqlConversionField($sqlConversionField)
    {
        $this->sqlConversionField = $sqlConversionField;
    }

    public function getSqlConversionField()
    {
        return $this->sqlConversionField;
    }
}
