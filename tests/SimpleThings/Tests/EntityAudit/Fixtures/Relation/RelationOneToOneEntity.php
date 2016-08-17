<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class RelationOneToOneEntity extends RelationAbstractEntityBase
{
    /** @ORM\OneToOne(targetEntity="RelationReferencedEntity", inversedBy="oneToOne") @ORM\JoinColumn(name="one_id", referencedColumnName="id_column") */
    protected $referencedEntity;

    public function getReferencedEntity()
    {
        return $this->referencedEntity;
    }
    public function setReferencedEntity($referencedEntity)
    {
        $this->referencedEntity = $referencedEntity;
    }
}
