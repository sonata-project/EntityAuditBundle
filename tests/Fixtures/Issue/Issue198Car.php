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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Issue198Car
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
     * @var Issue198Owner|null
     *
     * @ORM\ManyToOne(targetEntity="Issue198Owner", inversedBy="cars")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id")
     */
    private $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?Issue198Owner
    {
        return $this->owner;
    }

    public function setOwner(Issue198Owner $owner): void
    {
        $this->owner = $owner;
    }
}
