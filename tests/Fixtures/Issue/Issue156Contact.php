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

namespace Sonata\EntityAuditBundle\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 */
class Issue156Contact
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Collection<int, Issue156ContactTelephoneNumber>
     *
     * @ORM\OneToMany(targetEntity="Issue156ContactTelephoneNumber", mappedBy="contact")
     */
    private Collection $telephoneNumbers;

    public function __construct()
    {
        $this->telephoneNumbers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addTelephoneNumber(Issue156ContactTelephoneNumber $telephoneNumber): self
    {
        if (!$this->telephoneNumbers->contains($telephoneNumber)) {
            $telephoneNumber->setContact($this);
            $this->telephoneNumbers[] = $telephoneNumber;
        }

        return $this;
    }

    public function removeTelephoneNumber(Issue156ContactTelephoneNumber $telephoneNumber): self
    {
        $this->telephoneNumbers->removeElement($telephoneNumber);

        return $this;
    }

    /**
     * @return iterable<int, Issue156ContactTelephoneNumber>
     */
    public function getTelephoneNumbers(): iterable
    {
        return $this->telephoneNumbers;
    }
}
