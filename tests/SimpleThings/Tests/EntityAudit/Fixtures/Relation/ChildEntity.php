<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class ChildEntity extends AbstractParentEntity
{
    /**
     * @var RelatedEntity
     * @ORM\OneToOne(targetEntity="RelatedEntity")
     * @ORM\JoinColumn(name="relation_id", referencedColumnName="id", nullable=true)
     */
    private $relation;

    /**
     * @return RelatedEntity
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @param RelatedEntity $relation
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;
    }
}