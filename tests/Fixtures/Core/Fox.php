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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Fox extends AnimalAudit
{
    /**
     * @ORM\Column(type="integer", name="fox_tail_length")
     */
    private int $tailLength;

    public function __construct(string $name, int $tailLength)
    {
        $this->tailLength = $tailLength;
        parent::__construct($name);
    }

    public function getTailLength(): ?int
    {
        return $this->tailLength;
    }

    public function setTailLength(int $tailLength): void
    {
        $this->tailLength = $tailLength;
    }
}
