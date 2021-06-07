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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ArticleAudit
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="my_title_column")
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $text;

    /**
     * @ORM\Column(type="text")
     */
    private $ignoreMe;

    /**
     * @ORM\ManyToOne(targetEntity="UserAudit")
     */
    private $author;

    public function __construct(string $title, string $text, UserAudit $author, string $ignoreMe)
    {
        $this->title = $title;
        $this->text = $text;
        $this->author = $author;
        $this->ignoreMe = $ignoreMe;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?UserAudit
    {
        return $this->author;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setIgnoreMe(string $ignoreMe): void
    {
        $this->ignoreMe = $ignoreMe;
    }
}
