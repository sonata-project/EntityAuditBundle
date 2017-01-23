<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Cat extends PetAudit
{
    /** @ORM\Column(type="string", name="cute_cat_color") */
    private $color;

    public function __construct($name, $color)
    {
        $this->color = $color;
        parent::__construct($name);
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }
}
