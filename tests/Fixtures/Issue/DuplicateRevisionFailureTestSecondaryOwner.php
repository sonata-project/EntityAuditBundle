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

namespace SimpleThings\EntityAudit\Tests\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class DuplicateRevisionFailureTestSecondaryOwner extends DuplicateRevisionFailureTestEntity
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="DuplicateRevisionFailureTestPrimaryOwner",
     *     inversedBy="secondaryOwners"
     * )
     */
    private $primaryOwner;

    /**
     * @ORM\OneToMany(
     *     targetEntity="DuplicateRevisionFailureTestOwnedElement",
     *     mappedBy="secondaryOwner",
     *     cascade={"persist", "remove"}
     * )
     */
    private $elements;

    public function __construct()
    {
        $this->elements = new ArrayCollection();
    }

    public function setPrimaryOwner(DuplicateRevisionFailureTestPrimaryOwner $owner): void
    {
        $this->primaryOwner = $owner;
    }

    public function addElement(DuplicateRevisionFailureTestOwnedElement $element): void
    {
        $element->setSecondaryOwner($this);
        $this->elements->add($element);
    }
}
