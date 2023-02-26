<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"cheese" = "CheeseProduct", "wine" = "WineProduct"})
 */
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'discr', type: Types::STRING)]
#[ORM\DiscriminatorMap(['cheese' => CheeseProduct::class, 'wine' => WineProduct::class])]
abstract class Product extends SomeEntity
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    protected $name;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="products")
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    protected $category;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }
}
