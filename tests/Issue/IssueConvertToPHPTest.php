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

namespace SimpleThings\EntityAudit\Tests\Issue;

use SimpleThings\EntityAudit\Tests\BaseTest;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\ConvertToPHPEntity;
use SimpleThings\EntityAudit\Tests\Types\ConvertToPHPType;

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

        $persistedEntity = $this->em->find(\get_class($entity), $entity->getId());

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $currentRevision = $auditReader->getCurrentRevision(\get_class($entity), $entity->getId());
        $currentRevisionEntity = $auditReader->find(\get_class($entity), $entity->getId(), $currentRevision);

        static::assertSame(
            $persistedEntity->getSqlConversionField(),
            $currentRevisionEntity->getSqlConversionField(),
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }
}
