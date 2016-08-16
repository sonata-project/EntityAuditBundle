<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 23/02/2016
 * Time: 15:57
 */

namespace SimpleThings\Tests\EntityAudit\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Issue156Client
 * @package SimpleThings\Tests\EntityAudit\Fixtures\Issue
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
