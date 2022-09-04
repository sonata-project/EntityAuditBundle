<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Issue31User
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Issue31Reve", cascade={"persist", "remove"})
     */
    private ?Issue31Reve $reve = null;

    /**
     * @ORM\Column(type="string")
     */
    private ?string $titre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReve(): ?Issue31Reve
    {
        return $this->reve;
    }

    public function setReve(Issue31Reve $reve): void
    {
        $this->reve = $reve;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }
}
