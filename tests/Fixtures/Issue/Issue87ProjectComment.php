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
class Issue87ProjectComment
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
     * @ORM\ManyToOne(targetEntity="Issue87AbstractProject")
     * @ORM\JoinColumn(name="a_join_column")
     */
    private ?Issue87AbstractProject $project = null;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $text = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Issue87AbstractProject
    {
        return $this->project;
    }

    public function setProject(Issue87AbstractProject $project): void
    {
        $this->project = $project;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }
}
