<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Dog extends PetAudit
{
    /** @ORM\Column(type="integer", name="dog_tail_length") */
    private $tailLength;

    function __construct($name, $tailLength)
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
