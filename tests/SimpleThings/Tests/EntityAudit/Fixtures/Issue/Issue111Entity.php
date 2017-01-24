<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Issue111Entity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column */
    protected $status;

    /** @ORM\Column(type="datetime", nullable=true, name="deleted_at") */
    protected $deletedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }
}
