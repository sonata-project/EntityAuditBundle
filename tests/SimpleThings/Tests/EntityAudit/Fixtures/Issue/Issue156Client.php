<?php

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation\Auditable;

/**
 * @Auditable()
 * @ORM\Entity()
 */
class Issue156Client extends Issue156Contact
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $clientSpecificField;

    /**
     * @param string $clientSpecificField
     * @return $this
     */
    public function setClientSpecificField($clientSpecificField)
    {
        $this->clientSpecificField = $clientSpecificField;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSpecificField()
    {
        return $this->clientSpecificField;
    }
}
