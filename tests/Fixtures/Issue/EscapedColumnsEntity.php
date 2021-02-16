<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class EscapedColumnsEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="lft")
     */
    private $left;

    /**
     * @ORM\Column(type="integer", name="`left`")
     */
    private $lft;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLeft(): int
    {
        return $this->left;
    }

    public function setLeft($left): void
    {
        $this->left = $left;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setLft($lft): void
    {
        $this->lft = $lft;
    }
}
