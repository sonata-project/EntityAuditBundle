<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
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
