<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class DuplicateRevisionFailureTestSecondaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="DuplicateRevisionFailureTestPrimaryOwner",
     *     inversedBy="secondaryOwners"
     * )
     */
    private $primaryOwner;

    /**
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestOwnedElement",
     *     mappedBy="secondaryOwner",
     *     cascade={"persist", "remove"}
     * )
     */
    private $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner): void
    {
        $this->primaryOwner = $owner;
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element): void
    {
        $element->setSecondaryOwner($this);
        $this->elements->add($element);
    }
}
