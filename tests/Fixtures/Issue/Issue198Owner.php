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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Issue198Owner
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection<int, Issue198Car>
     *
     * @ORM\OneToMany(targetEntity="Issue198Car", mappedBy="owner")
     */
    private Collection $cars;

    public function __construct()
    {
        $this->cars = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addCar(Issue198Car $car): void
    {
        if (!$this->cars->contains($car)) {
            $car->setOwner($this);
            $this->cars[] = $car;
        }
    }

    public function removeCar(Issue198Car $car): void
    {
        $this->cars->removeElement($car);
    }

    /**
     * @return Collection<int, Issue198Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }
}
