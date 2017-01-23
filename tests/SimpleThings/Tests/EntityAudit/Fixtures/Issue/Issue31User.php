<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class Issue31User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31Reve", cascade={"persist", "remove"})
     */
    protected $reve;

    /** @ORM\Column(type="string") */
    protected $titre;

    public function getId()
    {
        return $this->id;
    }

    public function getReve()
    {
        return $this->reve;
    }

    public function setReve($reve)
    {
        $this->reve = $reve;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }
}
