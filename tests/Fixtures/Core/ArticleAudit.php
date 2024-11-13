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

#[ORM\Entity]
class ArticleAudit
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    public function __construct(
        #[ORM\Column(type: Types::STRING, name: 'my_title_column')]
        protected string $title,
        #[ORM\Column(type: Types::TEXT)]
        protected string $text,
        #[ORM\ManyToOne(targetEntity: UserAudit::class)]
        private ?UserAudit $author,
        #[ORM\Column(type: Types::TEXT)]
        protected string $ignoreme,
    ) {
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
