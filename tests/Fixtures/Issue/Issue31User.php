<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Issue31User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31Reve", cascade={"persist", "remove"})
     */
    private $reve;

    /**
     * @ORM\Column(type="string")
     */
    private $titre;

    public function getId(): int
    {
        return $this->id;
    }

    public function getReve(): ?Issue31Reve
    {
        return $this->reve;
    }

    public function setReve($reve): void
    {
        $this->reve = $reve;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre($titre): void
    {
        $this->titre = $titre;
    }
}
