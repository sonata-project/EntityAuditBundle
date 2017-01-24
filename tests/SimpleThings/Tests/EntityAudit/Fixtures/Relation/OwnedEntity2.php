<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class OwnedEntity2
{
    /** @ORM\Id @ORM\Column(type="integer", name="strange_owned_id_name") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string", name="even_strangier_column_name") */
    protected $title;

    /** @ORM\ManyToOne(targetEntity="OwnerEntity") @ORM\JoinColumn(name="owner_id_goes_here", referencedColumnName="some_strange_key_name")*/
    protected $owner;

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

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}
