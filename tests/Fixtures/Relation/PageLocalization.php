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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PageLocalization
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var Page|null
     */
    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'localizations')]
    protected $page;

    public function __construct(
        #[ORM\Column(type: Types::STRING)]
        protected string $locale,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
