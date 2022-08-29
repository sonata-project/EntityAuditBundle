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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\ConvertToPHPEntity;
use Sonata\EntityAuditBundle\Tests\Types\ConvertToPHPType;

final class IssueConvertToPHPTest extends BaseTest
{
    protected $schemaEntities = [
        ConvertToPHPEntity::class,
    ];

    protected $auditedEntities = [
        ConvertToPHPEntity::class,
    ];

    protected $customTypes = [
        'upper' => ConvertToPHPType::class,
    ];

    public function testConvertToPHP(): void
    {
        $entity = new ConvertToPHPEntity();
        $entity->setSqlConversionField('TEST CONVERT TO PHP');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entityId = $entity->getId();
        static::assertNotNull($entityId);

        $persistedEntity = $this->em->find(ConvertToPHPEntity::class, $entityId);
        static::assertNotNull($persistedEntity);

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $currentRevision = $auditReader->getCurrentRevision(ConvertToPHPEntity::class, $entityId);
        static::assertNotNull($currentRevision);
        $currentRevisionEntity = $auditReader->find(ConvertToPHPEntity::class, $entityId, $currentRevision);
        static::assertNotNull($currentRevisionEntity);

        static::assertSame(
            $persistedEntity->getSqlConversionField(),
            $currentRevisionEntity->getSqlConversionField(),
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }
}
