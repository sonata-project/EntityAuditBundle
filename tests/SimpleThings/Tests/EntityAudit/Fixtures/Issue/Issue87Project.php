<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity
 * @ORM\Table(name="project_project")
 */
class Issue87Project extends Issue87AbstractProject
{
    /**
     * @ORM\Column(type="string")
     */
    protected $someProperty;

    public function getSomeProperty()
    {
        return $this->someProperty;
    }

    public function setSomeProperty($someProperty)
    {
        $this->someProperty = $someProperty;
    }
}
