<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 */
class PageLocalization
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /** @ORM\Column(type="string") */
    private $locale;

    /** @ORM\ManyToOne(targetEntity="Page", inversedBy="localizations") */
    private $page;

    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPage(Page $page)
    {
        $this->page = $page;
    }

    public function getLocale()
    {
        return $this->locale;
    }
}

