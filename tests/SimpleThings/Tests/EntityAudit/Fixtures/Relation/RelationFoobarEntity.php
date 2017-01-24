<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class RelationFoobarEntity extends RelationReferencedEntity
{
    /** @ORM\Column(type="string") */
    protected $foobarField;

    public function getFoobarField()
    {
        return $this->foobarField;
    }

    public function setFoobarField($foobarField)
    {
        $this->foobarField = $foobarField;
    }
}
