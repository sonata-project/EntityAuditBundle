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
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\UnidirectionalManyToManyEntity;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\UnidirectionalManyToManyLinkedEntity;

final class IssueUnidirectionalManyToManyEntityTest extends BaseTest
{
    protected $schemaEntities = [
        UnidirectionalManyToManyEntity::class,
        UnidirectionalManyToManyLinkedEntity::class,
    ];

    protected $auditedEntities = [
        UnidirectionalManyToManyEntity::class,
        UnidirectionalManyToManyLinkedEntity::class,
    ];

    public function testUnidirectionalManyToManyAssociationWorksWhenTheMainEntityIsTheOneBeingPersistedFirst(): void
    {
        $entity = new UnidirectionalManyToManyEntity('foo');
        $entityTwo = new UnidirectionalManyToManyLinkedEntity('xyz');
        $entity->addLinkedEntity($entityTwo);

        $em = $this->getEntityManager();

        $em->persist($entity);
        $em->persist($entityTwo);
        $em->flush();

        $entityOneId = $entity->getId();
        $entityTwoId = $entityTwo->getId();

        \assert(\is_int($entityOneId));
        \assert(\is_int($entityTwoId));

        $this->assertAuditRecordsWereCorrectlyRecorded($entityOneId, $entityTwoId);
    }

    public function testUnidirectionalManyToManyAssociationWorksWhenTheLinkedEntityIsTheOneBeingPersistedFirst(): void
    {
        $entity = new UnidirectionalManyToManyEntity('foo');
        $entityTwo = new UnidirectionalManyToManyLinkedEntity('xyz');
        $entity->addLinkedEntity($entityTwo);

        $em = $this->getEntityManager();

        $em->persist($entityTwo);
        $em->persist($entity);
        $em->flush();

        $entityOneId = $entity->getId();
        $entityTwoId = $entityTwo->getId();

        \assert(\is_int($entityOneId));
        \assert(\is_int($entityTwoId));

        $this->assertAuditRecordsWereCorrectlyRecorded($entityOneId, $entityTwoId);
    }

    private function assertAuditRecordsWereCorrectlyRecorded(int $mainEntityId, int $linkedEntityId): void
    {
        $em = $this->getEntityManager();

        $em->clear();

        /** @var UnidirectionalManyToManyEntity $entity */
        $entity = $em->getRepository(UnidirectionalManyToManyEntity::class)->find($mainEntityId);
        /** @var UnidirectionalManyToManyLinkedEntity $entityTwo */
        $entityTwo = $em->getRepository(UnidirectionalManyToManyLinkedEntity::class)->find($linkedEntityId);

        $entity->setTitle('bar');
        $entityTwo->setName('zxy');
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $auditEntity = $reader->find(UnidirectionalManyToManyEntity::class, $mainEntityId, 1);
        static::assertInstanceOf(UnidirectionalManyToManyEntity::class, $auditEntity);
        static::assertSame('foo', $auditEntity->getTitle());
        static::assertCount(1, $auditEntity->getLinkedEntities());
        static::assertInstanceOf(UnidirectionalManyToManyLinkedEntity::class, $auditEntity->getLinkedEntities()[0]);
        static::assertSame('xyz', $auditEntity->getLinkedEntities()[0]->getName());

        $auditEntity = $reader->find(UnidirectionalManyToManyEntity::class, $mainEntityId, 2);
        static::assertInstanceOf(UnidirectionalManyToManyEntity::class, $auditEntity);
        static::assertSame('bar', $auditEntity->getTitle());
        static::assertCount(1, $auditEntity->getLinkedEntities());
        static::assertInstanceOf(UnidirectionalManyToManyLinkedEntity::class, $auditEntity->getLinkedEntities()[0]);
        static::assertSame('zxy', $auditEntity->getLinkedEntities()[0]->getName());

        $em->clear();

        /** @var UnidirectionalManyToManyEntity $entity */
        $entity = $em->getRepository(UnidirectionalManyToManyEntity::class)->find($mainEntityId);

        $em->remove($entity);
        $em->flush();

        $auditEntity = $reader->find(UnidirectionalManyToManyEntity::class, $mainEntityId, 3);
        static::assertInstanceOf(UnidirectionalManyToManyEntity::class, $auditEntity);
        static::assertSame('bar', $auditEntity->getTitle());
        static::assertCount(1, $auditEntity->getLinkedEntities());
        static::assertInstanceOf(UnidirectionalManyToManyLinkedEntity::class, $auditEntity->getLinkedEntities()[0]);
        static::assertSame('zxy', $auditEntity->getLinkedEntities()[0]->getName());
    }
}
