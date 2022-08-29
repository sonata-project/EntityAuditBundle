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
 * @ORM\Entity()
 */
class Issue156Client extends Issue156Contact
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $clientSpecificField = null;

    public function setClientSpecificField(string $clientSpecificField): self
    {
        $this->clientSpecificField = $clientSpecificField;

        return $this;
    }

    public function getClientSpecificField(): ?string
    {
        return $this->clientSpecificField;
    }
}
