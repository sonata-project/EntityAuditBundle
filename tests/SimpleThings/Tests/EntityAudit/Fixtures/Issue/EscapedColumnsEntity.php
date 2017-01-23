<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class EscapedColumnsEntity
{
    /** @ORM\Id @ORM\GeneratedValue() @ORM\Column(type="integer") */
    protected $id;

    /** @ORM\Column(type="integer", name="lft") */
    protected $left;

    /** @ORM\Column(type="integer", name="`left`") */
    protected $lft;

    public function getId()
    {
        return $this->id;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function setLeft($left)
    {
        $this->left = $left;
    }

    public function getLft()
    {
        return $this->lft;
    }

    public function setLft($lft)
    {
        $this->lft = $lft;
    }
}
