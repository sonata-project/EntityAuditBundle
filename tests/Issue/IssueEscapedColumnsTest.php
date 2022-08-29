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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\EscapedColumnsEntity;

final class IssueEscapedColumnsTest extends BaseTest
{
    protected $schemaEntities = [
        EscapedColumnsEntity::class,
    ];

    protected $auditedEntities = [
        EscapedColumnsEntity::class,
    ];

    public function testEscapedColumns(): void
    {
        $e = new EscapedColumnsEntity();
        $e->setLeft(1);
        $e->setLft(2);
        $this->em->persist($e);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $eId = $e->getId();
        static::assertNotNull($eId);

        $reader->find(EscapedColumnsEntity::class, $eId, 1);
    }
}
