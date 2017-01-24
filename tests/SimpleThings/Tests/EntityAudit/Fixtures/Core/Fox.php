<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Fox extends AnimalAudit
{
    /** @ORM\Column(type="integer", name="fox_tail_length") */
    private $tailLength;

    public function __construct($name, $tailLength)
    {
        $this->tailLength = $tailLength;
        parent::__construct($name);
    }

    public function getTailLength()
    {
        return $this->tailLength;
    }

    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
    }
}
