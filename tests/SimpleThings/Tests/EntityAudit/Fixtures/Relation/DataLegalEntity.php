<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * Legal data entity
 *
 * @ORM\Entity
 */
class DataLegalEntity extends AbstractDataEntity
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $company;

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }
}
