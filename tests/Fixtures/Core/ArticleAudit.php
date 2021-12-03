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
    protected $id;

    /**
     * @ORM\Column(type="string", name="my_title_column")
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     */
    protected $text;

    /**
     * @ORM\Column(type="text")
     */
    protected $ignoreme;

    /**
     * @ORM\ManyToOne(targetEntity="UserAudit")
     */
    private $author;

    public function __construct(string $title, string $text, UserAudit $author, string $ignoreme)
    {
        $this->title = $title;
        $this->text = $text;
        $this->author = $author;
        $this->ignoreme = $ignoreme;
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

    public function setIgnoreme(string $ignoreme): void
    {
        $this->ignoreme = $ignoreme;
    }
}
