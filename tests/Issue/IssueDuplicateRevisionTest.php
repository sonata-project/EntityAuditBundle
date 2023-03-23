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

namespace Sonata\EntityAuditBundle\Tests\Issue;

use Sonata\EntityAuditBundle\Tests\BaseTest;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\DuplicateRevisionFailureTestOwnedElement;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\DuplicateRevisionFailureTestPrimaryOwner;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\DuplicateRevisionFailureTestSecondaryOwner;

final class IssueDuplicateRevisionTest extends BaseTest
{
    protected $schemaEntities = [
        DuplicateRevisionFailureTestPrimaryOwner::class,
        DuplicateRevisionFailureTestSecondaryOwner::class,
        DuplicateRevisionFailureTestOwnedElement::class,
    ];

    protected $auditedEntities = [
        DuplicateRevisionFailureTestPrimaryOwner::class,
        DuplicateRevisionFailureTestSecondaryOwner::class,
        DuplicateRevisionFailureTestOwnedElement::class,
    ];

    public function testDuplicateRevisionKeyConstraintFailure(): void
    {
        $em = $this->getEntityManager();

        $primaryOwner = new DuplicateRevisionFailureTestPrimaryOwner();
        $em->persist($primaryOwner);

        $secondaryOwner = new DuplicateRevisionFailureTestSecondaryOwner();
        $em->persist($secondaryOwner);

        $primaryOwner->addSecondaryOwner($secondaryOwner);

        $element = new DuplicateRevisionFailureTestOwnedElement();
        $em->persist($element);

        $primaryOwner->addElement($element);
        $secondaryOwner->addElement($element);

        $em->flush();

        $em->getUnitOfWork()->clear();

        $primaryOwner = $em->find(DuplicateRevisionFailureTestPrimaryOwner::class, 1);
        static::assertNotNull($primaryOwner);

        $em->remove($primaryOwner);
        $em->flush();
    }
}
