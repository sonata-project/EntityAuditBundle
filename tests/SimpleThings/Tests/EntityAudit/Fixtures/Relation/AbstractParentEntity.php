<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractParentEntity
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column(name="relation_id", type="integer", nullable=true) */
    private $relationId;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getRelationId()
    {
        return $this->relationId;
    }

    /**
     * @param mixed $relationId
     */
    public function setRelationId($relationId)
    {
        $this->relationId = $relationId;
    }
}
