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
 * Data container entity.
 *
 * @ORM\Entity
 */
class DataContainerEntity
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="AbstractDataEntity", inversedBy="dataContainer", cascade={"persist", "remove"})
     */
    private ?AbstractDataEntity $data = null;

    /**
     * @ORM\Column(type="string")
     */
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
