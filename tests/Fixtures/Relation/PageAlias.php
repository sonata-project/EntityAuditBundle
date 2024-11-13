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
 * A slightly contrived entity which has an entity (Page) as an ID.
 */
#[ORM\Entity]
class PageAlias
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'associatedEmails', cascade: ['persist'])]
        #[ORM\JoinColumn(name: 'page_id', referencedColumnName: 'id', nullable: false)]
        protected Page $page,
        #[ORM\Column(type: Types::STRING, nullable: false, length: 255, unique: true)]
        protected ?string $alias = null,
    ) {
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }
}
