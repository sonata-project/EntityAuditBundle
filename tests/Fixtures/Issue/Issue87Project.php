<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="project_project")
 */
final class Issue87Project extends Issue87AbstractProject
{
    /**
     * @ORM\Column(type="string")
     */
    private $someProperty;

    public function getSomeProperty(): ?string
    {
        return $this->someProperty;
    }

    public function setSomeProperty($someProperty): void
    {
        $this->someProperty = $someProperty;
    }
}
