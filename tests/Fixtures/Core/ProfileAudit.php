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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ProfileAudit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $biography;

    /**
     * @ORM\Column(type="string")
     */
    private $ignoreme;

    /**
     * @ORM\OneToOne(targetEntity="UserAudit", inversedBy="profile")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    private $user;

    public function __construct(string $biography)
    {
        $this->biography = $biography;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(string $biography): void
    {
        $this->biography = $biography;
    }

    public function getUser(): ?UserAudit
    {
        return $this->user;
    }

    public function setUser(UserAudit $user): void
    {
        $this->user = $user;
    }

    public function setIgnoreme(string $ignoreme): void
    {
        $this->ignoreme = $ignoreme;
    }

    public function getIgnoreme(): ?string
    {
        return $this->ignoreme;
    }
}
