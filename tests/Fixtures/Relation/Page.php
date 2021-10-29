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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
{
    /**
     * A page can have many aliases.
     *
     * @var PageAlias[]
     * @ORM\OneToMany(targetEntity="PageAlias", mappedBy="page", cascade={"persist"})
     */
    protected $pageAliases;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="PageLocalization", mappedBy="page", indexBy="locale")
     */
    private $localizations;

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

    public function getLocalizations(): ?Collection
    {
        return $this->localizations;
    }

    public function addLocalization(PageLocalization $localization): void
    {
        $localization->setPage($this);
        $this->localizations->set($localization->getLocale(), $localization);
    }
}
