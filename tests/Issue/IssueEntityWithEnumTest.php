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
use Sonata\EntityAuditBundle\Tests\Fixtures\PHP81Issue\IssueEntityWithEnum;
use Sonata\EntityAuditBundle\Tests\Fixtures\PHP81Issue\Status;

/**
 * @requires PHP 8.1
 */
final class IssueEntityWithEnumTest extends BaseTest
{
    protected $schemaEntities = [
        IssueEntityWithEnum::class,
    ];

    protected $auditedEntities = [
        IssueEntityWithEnum::class,
    ];

    public function testIssueEntityWithEnums(): void
    {
        $entity = new IssueEntityWithEnum(Status::Foo);

        $this->em->persist($entity);
        $this->em->flush();

        $entityId = $entity->getId();

        \assert(\is_int($entityId));

        $this->em->clear();

        /** @var IssueEntityWithEnum $entity */
        $entity = $this->em->getRepository(IssueEntityWithEnum::class)->findOneBy(['id' => $entityId]);

        $entity->setStatus(Status::Qwe);
        $this->em->flush();

        $reader = $this->auditManager->createAuditReader($this->em);

        $auditEntity = $reader->find(IssueEntityWithEnum::class, $entityId, 1);
        static::assertInstanceOf(IssueEntityWithEnum::class, $auditEntity);
        static::assertSame(Status::Foo, $auditEntity->getStatus());

        $auditEntity = $reader->find(IssueEntityWithEnum::class, $entityId, 2);
        static::assertInstanceOf(IssueEntityWithEnum::class, $auditEntity);
        static::assertSame(Status::Qwe, $auditEntity->getStatus());

        $this->em->clear();

        /** @var IssueEntityWithEnum $entity */
        $entity = $this->em->getRepository(IssueEntityWithEnum::class)->findOneBy(['id' => $entityId]);

        $this->em->remove($entity);
        $this->em->flush();

        $auditEntity = $reader->find(IssueEntityWithEnum::class, $entityId, 3);
        static::assertInstanceOf(IssueEntityWithEnum::class, $auditEntity);
        static::assertSame(Status::Qwe, $auditEntity->getStatus());
    }
}
