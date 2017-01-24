<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Issue194Address
{
    /** @ORM\Id @ORM\OneToOne(targetEntity="Issue194User") */
    private $user;

    public function __construct(Issue194User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
