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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RelationFoobarEntity extends RelationReferencedEntity
{
    /**
     * @ORM\Column(type="string")
     */
    protected $foobarField;

    public function getFoobarField(): ?string
    {
        return $this->foobarField;
    }

    public function setFoobarField(string $foobarField): void
    {
        $this->foobarField = $foobarField;
    }
}
