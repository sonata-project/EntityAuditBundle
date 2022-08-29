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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="project_project")
 */
class Issue87Project extends Issue87AbstractProject
{
    /**
     * @ORM\Column(type="string")
     */
    private ?string $someProperty = null;

    public function getSomeProperty(): ?string
    {
        return $this->someProperty;
    }

    public function setSomeProperty(string $someProperty): void
    {
        $this->someProperty = $someProperty;
    }
}
