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
class DataContainerEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: AbstractDataEntity::class, inversedBy: 'dataContainer', cascade: ['persist', 'remove'])]
    private ?AbstractDataEntity $data = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): ?AbstractDataEntity
    {
        return $this->data;
    }

    public function setData(AbstractDataEntity $data): void
    {
        $this->data = $data;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
