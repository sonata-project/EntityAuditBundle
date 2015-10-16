<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"rabbit" = "Rabbit", "fox" = "Fox"})
 */
abstract class AnimalAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
