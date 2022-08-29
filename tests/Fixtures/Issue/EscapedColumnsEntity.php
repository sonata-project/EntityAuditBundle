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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class EscapedColumnsEntity
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", name="lft")
     */
    private ?int $left = null;

    /**
     * @ORM\Column(type="integer", name="`left`")
     */
    private ?int $lft = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeft(): ?int
    {
        return $this->left;
    }

    public function setLeft(?int $left = null): void
    {
        $this->left = $left;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(?int $lft = null): void
    {
        $this->lft = $lft;
    }
}
