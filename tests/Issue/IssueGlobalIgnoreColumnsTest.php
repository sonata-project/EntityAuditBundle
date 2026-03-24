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

use Sonata\EntityAuditBundle\Tests\BaseTestCase;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\IssueGlobalIgnoreColumnsEntity;

final class IssueGlobalIgnoreColumnsTest extends BaseTestCase
{
    protected $schemaEntities = [
        IssueGlobalIgnoreColumnsEntity::class,
    ];

    protected $auditedEntities = [
        IssueGlobalIgnoreColumnsEntity::class,
    ];

    public function testGlobalIgnoreColumns(): void
    {
        $entity = new IssueGlobalIgnoreColumnsEntity();
        $entity->setIgnoreme('test1')
            ->setName('name1');

        $em = $this->getEntityManager();

        $em->persist($entity);
        $em->flush();
        $em->clear();

        $entityId = $entity->getId();
        static::assertNotNull($entityId);

        $persistedEntity = $em->find(IssueGlobalIgnoreColumnsEntity::class, $entityId);
        static::assertNotNull($persistedEntity);

        $this->getAuditManager();
        $cnt = $em->getConnection()->executeQuery('SELECT COUNT(*) FROM revisions')->fetchOne();

        $entity = $em->find(IssueGlobalIgnoreColumnsEntity::class, $entityId);
        static::assertInstanceOf(IssueGlobalIgnoreColumnsEntity::class, $entity);
        $entity->setIgnoreme('test2')->setName('name2');
        $em->flush();
        $em->clear();

        $newCnt = $em->getConnection()->executeQuery('SELECT COUNT(*) FROM revisions')->fetchOne();
        static::assertSame($cnt + 1, $newCnt);

        $entity = $em->find(IssueGlobalIgnoreColumnsEntity::class, $entityId);
        static::assertInstanceOf(IssueGlobalIgnoreColumnsEntity::class, $entity);
        $entity->setIgnoreme('test2');
        $em->flush();
        $em->clear();

        $newCnt = $em->getConnection()->executeQuery('SELECT COUNT(*) FROM revisions')->fetchOne();
        static::assertSame($cnt + 1, $newCnt);
    }
}
