<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
final class ConvertToPHPEntity
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="upper")
     */
    private $sqlConversionField;

    public function getId(): int
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
