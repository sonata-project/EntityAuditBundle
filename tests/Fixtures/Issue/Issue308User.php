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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue308User
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Issue308User|null
     *
     * @ORM\ManyToOne(targetEntity="Issue308User", inversedBy="children")
     */
    protected $parent;

    /**
     * @var Collection<int, self>
     *
     * @ORM\OneToMany(targetEntity="Issue308User", mappedBy="parent")
     */
    private Collection $children;

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

    /**
     * @return ReadableCollection<int, self>
     */
    public function getChildren(): ReadableCollection
    {
        $activeChildren = $this->children->filter(
            static fn (self $user): bool => $user->isActive()
        );

        return $activeChildren;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }
}
