<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * Data container entity
 *
 * @Auditable()
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
    private $id;

    /**
     * @var AbstractDataEntity
     *
     * @ORM\OneToOne(targetEntity="AbstractDataEntity", inversedBy="dataContainer", cascade={"persist", "remove"})
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AbstractDataEntity
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param AbstractDataEntity $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
