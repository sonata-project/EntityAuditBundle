<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Issue196Entity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    private $id;

    /** @ORM\Column(type="issue196type") */
    private $sqlConversionField;

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
