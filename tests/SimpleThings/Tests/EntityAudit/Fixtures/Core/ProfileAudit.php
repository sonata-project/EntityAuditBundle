<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class ProfileAudit
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(type="text") */
    private $biography;

    /** @ORM\OneToOne(targetEntity="UserAudit", inversedBy="profile") @ORM\JoinColumn(referencedColumnName="id", nullable=true) */
    private $user;

    public function __construct($biography)
    {
        $this->biography = $biography;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBiography()
    {
        return $this->biography;
    }

    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }
}
