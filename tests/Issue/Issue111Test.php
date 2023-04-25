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

use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Sonata\EntityAuditBundle\Tests\BaseTest;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue111Entity;

final class Issue111Test extends BaseTest
{
    protected $schemaEntities = [
        Issue111Entity::class,
    ];

    protected $auditedEntities = [
        Issue111Entity::class,
    ];

    public function testIssue111(): void
    {
        $em = $this->getEntityManager();

        $em->getEventManager()->addEventSubscriber(new SoftDeleteableListener());

        $e = new Issue111Entity();
        $e->setStatus('test status');

        $em->persist($e);
        $em->flush(); // #1

        $em->remove($e);
        $em->flush(); // #2

        $reader = $this->getAuditManager()->createAuditReader($em);

        $ae = $reader->find(Issue111Entity::class, 1, 2);
        static::assertNotNull($ae);

        static::assertInstanceOf(\DateTime::class, $ae->getDeletedAt());
    }
}
