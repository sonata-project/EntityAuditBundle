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
 * NB! Object property order matters!
 *
 * @ORM\Entity
 */
class DuplicateRevisionFailureTestPrimaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @var Collection<int, DuplicateRevisionFailureTestOwnedElement>
     *
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestOwnedElement",
     *     mappedBy="primaryOwner",
     *     cascade={"persist", "remove"},
     *     fetch="LAZY"
     * )
     */
    private Collection $elements;

    /**
     * @var Collection<int, DuplicateRevisionFailureTestSecondaryOwner>
     *
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestSecondaryOwner",
     *     mappedBy="primaryOwner",
     *     cascade={"persist", "remove"}
     * )
     */
    private Collection $secondaryOwners;

    public function __construct()
    {
        $this->secondaryOwners = new ArrayCollection();
        $this->elements = new ArrayCollection();
    }

    public function addSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $secondaryOwner): void
    {
        $secondaryOwner->setPrimaryOwner($this);
        $this->secondaryOwners->add($secondaryOwner);
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element): void
    {
        $element->setPrimaryOwner($this);
        $this->elements->add($element);
    }
}
