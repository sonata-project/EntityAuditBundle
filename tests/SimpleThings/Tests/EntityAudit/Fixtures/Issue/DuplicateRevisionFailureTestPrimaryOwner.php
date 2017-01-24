<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * NB! Object property order matters!
 *
 * @Auditable()
 * @ORM\Entity
 */
class DuplicateRevisionFailureTestPrimaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\OneToMany(targetEntity="DuplicateRevisionFailureTestOwnedElement", mappedBy="primaryOwner",
     *                                                                         cascade={"persist", "remove"},
     *                                                                         fetch="LAZY")
     */
    protected $elements;

    /**
     * @ORM\OneToMany(targetEntity="DuplicateRevisionFailureTestSecondaryOwner", mappedBy="primaryOwner",
     *                                                                           cascade={"persist", "remove"})
     */
    protected $secondaryOwners;

    public function __construct()
    {
        $this->secondaryOwners = new ArrayCollection();
        $this->elements        = new ArrayCollection();
    }

    public function addSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $secondaryOwner)
    {
        $secondaryOwner->setPrimaryOwner($this);
        $this->secondaryOwners->add($secondaryOwner);
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element)
    {
        $element->setPrimaryOwner($this);
        $this->elements->add($element);
    }
}
