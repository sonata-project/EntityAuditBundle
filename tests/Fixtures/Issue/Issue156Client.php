<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 23/02/2016
 * Time: 15:57
 */

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Issue156Client
 * @package SimpleThings\EntityAudit\Tests\Fixtures\Issue
 * @ORM\Entity()
 */
final class Issue156Client extends Issue156Contact
{
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $clientSpecificField;

    public function setClientSpecificField(string $clientSpecificField): self
    {
        $this->clientSpecificField = $clientSpecificField;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientSpecificField(): ?string
    {
        return $this->clientSpecificField;
    }
}
