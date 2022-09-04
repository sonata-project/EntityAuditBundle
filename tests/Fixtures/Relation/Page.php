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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
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
     * A page can have many aliases.
     *
     * @var Collection<int, PageAlias>
     *
     * @ORM\OneToMany(targetEntity="PageAlias", mappedBy="page", cascade={"persist"})
     */
    protected $pageAliases;

    /**
     * @var Collection<string, PageLocalization>
     *
     * @ORM\OneToMany(targetEntity="PageLocalization", mappedBy="page", indexBy="locale")
     */
    protected $localizations;

    public function __construct()
    {
        $this->localizations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<string, PageLocalization>
     */
    public function getLocalizations(): Collection
    {
        return $this->localizations;
    }

    public function addLocalization(PageLocalization $localization): void
    {
        $localization->setPage($this);
        $this->localizations->set($localization->getLocale() ?? '', $localization);
    }
}
