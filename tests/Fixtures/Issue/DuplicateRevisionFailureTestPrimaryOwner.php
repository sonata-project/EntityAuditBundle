<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * NB! Object property order matters!
 *
 * @ORM\Entity
 */
final class DuplicateRevisionFailureTestPrimaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestOwnedElement",
     *     mappedBy="primaryOwner",
     *     cascade={"persist", "remove"},
     *     fetch="LAZY"
     * )
     */
    private $elements;

    /**
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestSecondaryOwner",
     *     mappedBy="primaryOwner",
     *     cascade={"persist", "remove"}
     * )
     */
    private $secondaryOwners;

    public function __construct()
    {
        $this->secondaryOwners = new ArrayCollection();
        $this->elements        = new ArrayCollection();
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
