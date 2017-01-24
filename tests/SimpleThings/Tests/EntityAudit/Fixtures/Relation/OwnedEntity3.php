<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class OwnedEntity3
{
    /** @ORM\Id @ORM\Column(type="integer", name="strange_owned_id_name") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string", name="even_strangier_column_name") */
    protected $title;

    /** @ORM\ManyToMany(targetEntity="OwnerEntity", inversedBy="owned3")
     * @ORM\JoinTable(name="owner_owned3",
     *   joinColumns={@ORM\JoinColumn(name="owned3_id", referencedColumnName="strange_owned_id_name")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="some_strange_key_name")}
     * )
     */
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
