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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class RelationOneToOneEntity extends RelationAbstractEntityBase
{
    /**
     * @var RelationReferencedEntity|null
     *
     * @ORM\OneToOne(targetEntity="RelationReferencedEntity", inversedBy="oneToOne")
     * @ORM\JoinColumn(name="one_id", referencedColumnName="id_column")
     */
    protected $referencedEntity;

    public function getReferencedEntity(): ?RelationReferencedEntity
    {
        return $this->referencedEntity;
    }

    public function setReferencedEntity(RelationReferencedEntity $referencedEntity): void
    {
        $this->referencedEntity = $referencedEntity;
    }
}
