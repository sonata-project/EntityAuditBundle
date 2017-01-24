<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"cheese" = "CheeseProduct", "wine" = "WineProduct"})
 */
abstract class Product extends SomeEntity
{
    /** @ORM\Column(type="string") */
    private $name;

    /** @ORM\ManyToOne(targetEntity="Category", inversedBy="products") */
    private $category;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setCategory(Category $category)
    {
        $this->category = $category;
    }
}
