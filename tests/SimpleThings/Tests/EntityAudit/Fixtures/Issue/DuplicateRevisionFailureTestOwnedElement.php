<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class DuplicateRevisionFailureTestOwnedElement extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestPrimaryOwner", inversedBy="elements")
     */
    protected $primaryOwner;

    /**
     * @ORM\ManyToOne(targetEntity="DuplicateRevisionFailureTestSecondaryOwner", inversedBy="elements")
     */
    protected $secondaryOwner;

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner)
    {
        $this->primaryOwner = $owner;
    }

    public function setSecondaryOwner(DuplicateRevisionFailureTestSecondaryOwner $owner)
    {
        $this->secondaryOwner = $owner;
    }
}
