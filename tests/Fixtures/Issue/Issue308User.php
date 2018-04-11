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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue308User
{
    /**
     * @var int
     *
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Issue308User", mappedBy="parent")
     */
    private $children;

    /**
     * @var Issue308User
     *
     * @ORM\ManyToOne(targetEntity="Issue308User", inversedBy="children")
     */
    private $parent;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @transient
     */
    public function isActive(): bool
    {
        return false;
    }

    public function addChild(self $child): void
    {
        $this->children->add($child);
    }

    public function getChildren(): Collection
    {
        $activeChildren = $this->children->filter(static function (self $user): bool {
            return $user->isActive();
        });

        return $activeChildren;
    }

    public function getParent(): self
    {
        return $this->parent;
    }
}
