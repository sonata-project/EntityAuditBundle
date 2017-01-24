<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class OneToOneMasterEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $title;

    /** @ORM\OneToOne(targetEntity="OneToOneAuditedEntity") @ORM\JoinColumn(onDelete="SET NULL") */
    protected $audited;

    /** @ORM\OneToOne(targetEntity="OneToOneNotAuditedEntity") */
    protected $notAudited;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getAudited()
    {
        return $this->audited;
    }

    public function setAudited($audited)
    {
        $this->audited = $audited;
    }

    public function getNotAudited()
    {
        return $this->notAudited;
    }

    public function setNotAudited($notAudited)
    {
        $this->notAudited = $notAudited;
    }
}
