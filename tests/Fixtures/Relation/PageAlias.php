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

use Doctrine\ORM\Mapping as ORM;

/**
 * A slightly contrived entity which has an entity (Page) as an ID.
 *
 * @ORM\Entity
 */
class PageAlias
{
    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="associatedEmails", cascade={"persist"})
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=false)
     * @ORM\Id
     *
     * @var Page
     */
    protected $page;

    /**
     * @var string|null
     *
     * @ORM\Column( type="string", nullable=false, length=255, unique=true)
     * )
     */
    protected $alias;

    public function __construct(Page $page, ?string $alias = null)
    {
        $this->page = $page;
        $this->alias = $alias;
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
