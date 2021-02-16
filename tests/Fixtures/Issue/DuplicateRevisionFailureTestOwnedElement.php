<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class DuplicateRevisionFailureTestOwnedElement extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestPrimaryOwner", inversedBy="elements")
     */
    private $primaryOwner;

    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestSecondaryOwner", inversedBy="elements")
     */
    private $secondaryOwner;

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner): void
    {
        $this->primaryOwner = $owner;
    }

    public function setSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $owner): void
    {
        $this->secondaryOwner = $owner;
    }
}
