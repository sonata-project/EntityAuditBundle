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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class OneToOneMasterEntity
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string")
     */
    #[ORM\Column(type: Types::STRING)]
    protected $title;

    /**
     * @var OneToOneAuditedEntity|null
     *
     * @ORM\OneToOne(targetEntity="OneToOneAuditedEntity")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    #[ORM\OneToOne(targetEntity: OneToOneAuditedEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected $audited;

    /**
     * @var OneToOneNotAuditedEntity|null
     *
     * @ORM\OneToOne(targetEntity="OneToOneNotAuditedEntity")
     */
    #[ORM\OneToOne(targetEntity: OneToOneNotAuditedEntity::class)]
    protected $notAudited;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAudited(): ?OneToOneAuditedEntity
    {
        return $this->audited;
    }

    public function setAudited(OneToOneAuditedEntity $audited): void
    {
        $this->audited = $audited;
    }

    public function getNotAudited(): ?OneToOneNotAuditedEntity
    {
        return $this->notAudited;
    }

    public function setNotAudited(OneToOneNotAuditedEntity $notAudited): void
    {
        $this->notAudited = $notAudited;
    }
}
