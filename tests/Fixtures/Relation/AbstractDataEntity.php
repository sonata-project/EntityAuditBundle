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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: Types::STRING)]
#[ORM\DiscriminatorMap(['private' => DataPrivateEntity::class, 'legal' => DataLegalEntity::class])]
abstract class AbstractDataEntity
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: DataContainerEntity::class, mappedBy: 'data')]
    private ?DataContainerEntity $dataContainer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataContainer(): ?DataContainerEntity
    {
        return $this->dataContainer;
    }

    public function setDataContainer(DataContainerEntity $dataContainer): void
    {
        $this->dataContainer = $dataContainer;
    }
}
