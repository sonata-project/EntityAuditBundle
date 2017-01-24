<?php
/**
 * Created by PhpStorm.
 * User: doconnell
 * Date: 12/10/16
 * Time: 08:49
 */

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * A slightly contrived entity which has an entity (Page) as an ID.
 * @Auditable()
 * @ORM\Entity
 */
class PageAlias
{
    /**
     * @ORM\ManyToOne(targetEntity="Page", inversedBy="associatedEmails", cascade={"persist"})
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=false)
     * @ORM\Id
     * @var Page
     */
    protected $page;

    /**
     * @var string
     * @ORM\Column( type="string", nullable=false, length=255, unique=true)
     * )
     */
    protected $alias;

    public function __construct(Page $page, $alias = null)
    {
        $this->page = $page;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return self
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
}
