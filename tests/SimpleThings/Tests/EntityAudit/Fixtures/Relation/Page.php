<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Page
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    private $id;

    /** @ORM\OneToMany(targetEntity="PageLocalization", mappedBy="page", indexBy="locale") */
    private $localizations;

    public function __construct()
    {
        $this->localizations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getLocalizations()
    {
        return $this->localizations;
    }

    public function addLocalization(PageLocalization $localization)
    {
        $localization->setPage($this);
        $this->localizations->set($localization->getLocale(), $localization);
    }
}
