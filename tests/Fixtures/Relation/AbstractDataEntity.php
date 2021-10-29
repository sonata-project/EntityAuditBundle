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
 * Abstract data entity.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"private" = "DataPrivateEntity", "legal" = "DataLegalEntity"})
 */
abstract class AbstractDataEntity
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DataContainerEntity
     *
     * @ORM\OneToOne(targetEntity="DataContainerEntity", mappedBy="data")
     */
    private $dataContainer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataContainer(): ?DataContainerEntity
    {
        return $this->dataContainer;
    }

    public function setDataContainer(DataContainerEntity $dataContainer)
    {
        $this->dataContainer = $dataContainer;
    }
}
