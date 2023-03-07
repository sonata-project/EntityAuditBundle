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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class ArticleAudit
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="my_title_column")
     */
    #[ORM\Column(type: Types::STRING, name: 'my_title_column')]
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    #[ORM\Column(type: Types::TEXT)]
    protected $text;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    #[ORM\Column(type: Types::TEXT)]
    protected $ignoreme;

    public function __construct(string $title, string $text, /**
     * @ORM\ManyToOne(targetEntity="UserAudit")
     */
    #[ORM\ManyToOne(targetEntity: UserAudit::class)]
    private ?UserAudit $author, string $ignoreme)
    {
        $this->title = $title;
        $this->text = $text;
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
