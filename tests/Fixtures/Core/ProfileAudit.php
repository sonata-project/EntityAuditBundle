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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ProfileAudit
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    #[ORM\OneToOne(targetEntity: UserAudit::class, inversedBy: 'profile')]
    #[ORM\JoinColumn(referencedColumnName: 'id', nullable: true)]
    private ?UserAudit $user = null;

    public function __construct(
        #[ORM\Column(type: Types::TEXT)]
        private string $biography,
    ) {
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
}
