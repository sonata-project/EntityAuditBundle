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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PageLocalization
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="localizations")
     */
    private $page;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
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
