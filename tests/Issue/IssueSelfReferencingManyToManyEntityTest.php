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
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\BaseSelfReferencingManyToManyEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\SelfReferencingManyToManyEntity;

final class IssueSelfReferencingManyToManyEntityTest extends BaseTest
{
    protected $schemaEntities = [
        BaseSelfReferencingManyToManyEntity::class,
        SelfReferencingManyToManyEntity::class,
    ];

    protected $auditedEntities = [
        SelfReferencingManyToManyEntity::class,
    ];

    public function testSelfReferencingManyToManyAssociationWithClassTableInheritanceWorks(): void
    {
        $entity = new SelfReferencingManyToManyEntity('foo');
        $entityTwo = new SelfReferencingManyToManyEntity('xyz');
        $entity->addLinkedEntity($entityTwo);

        $this->em->persist($entity);
        $this->em->persist($entityTwo);
        $this->em->flush();

        $entityId = $entity->getId();

        \assert(\is_int($entityId));

        $this->em->clear();

        /** @var SelfReferencingManyToManyEntity $entity */
        $entity = $this->em->getRepository(SelfReferencingManyToManyEntity::class)->find($entityId);

        $entity->setTitle('bar');
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $auditEntity = $reader->find(SelfReferencingManyToManyEntity::class, $entityId, 1);
        static::assertInstanceOf(SelfReferencingManyToManyEntity::class, $auditEntity);
        static::assertSame('foo', $auditEntity->getTitle());

        $auditEntity = $reader->find(SelfReferencingManyToManyEntity::class, $entityId, 2);
        static::assertInstanceOf(SelfReferencingManyToManyEntity::class, $auditEntity);
        static::assertSame('bar', $auditEntity->getTitle());

        $this->em->clear();

        /** @var SelfReferencingManyToManyEntity $entity */
        $entity = $this->em->getRepository(SelfReferencingManyToManyEntity::class)->find($entityId);

        $this->em->remove($entity);
        $this->em->flush();

        $auditEntity = $reader->find(SelfReferencingManyToManyEntity::class, $entityId, 3);
        static::assertInstanceOf(SelfReferencingManyToManyEntity::class, $auditEntity);
        static::assertSame('bar', $auditEntity->getTitle());
    }
}
