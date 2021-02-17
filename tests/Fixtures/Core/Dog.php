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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Core;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
final class Dog extends PetAudit
{
    /**
     * @ORM\Column(type="integer", name="dog_tail_length")
     */
    private $tailLength;

    public function __construct($name, $tailLength)
    {
        $this->tailLength = $tailLength;
        parent::__construct($name);
    }

    public function getTailLength()
    {
        return $this->tailLength;
    }

    public function setTailLength($tailLength)
    {
        $this->tailLength = $tailLength;
    }
}
