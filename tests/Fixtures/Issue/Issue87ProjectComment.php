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
 * @ORM\Entity
 */
final class Issue87ProjectComment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManytoOne(targetEntity="Issue87AbstractProject")
     * @ORM\JoinColumn(name="a_join_column")
     */
    private $project;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProject(): ?Issue87AbstractProject
    {
        return $this->project;
    }

    public function setProject($project): void
    {
        $this->project = $project;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText($text): void
    {
        $this->text = $text;
    }
}
