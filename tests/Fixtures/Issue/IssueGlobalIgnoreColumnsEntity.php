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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @psalm-suppress ClassMustBeFinal
 */
#[ORM\Entity]
class IssueGlobalIgnoreColumnsEntity
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(type: Types::STRING)]
    private ?string $ignoreme = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIgnoreme(): ?string
    {
        return $this->ignoreme;
    }

    public function setIgnoreme(?string $ignoreme): self
    {
        $this->ignoreme = $ignoreme;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
