<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Issue31Reve
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31User")
     */
    private $user;

    /**
     * @ORM\Column(type="string")
     */
    private $titre;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): ?Issue31User
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
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
