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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue196Entity;
use Sonata\EntityAuditBundle\Tests\Types\Issue196Type;

final class Issue196Test extends BaseTest
{
    protected $schemaEntities = [
        Issue196Entity::class,
    ];

    protected $auditedEntities = [
        Issue196Entity::class,
    ];

    protected $customTypes = [
        'issue196type' => Issue196Type::class,
    ];

    public function testIssue196(): void
    {
        $entity = new Issue196Entity();
        $entity->setSqlConversionField('THIS SHOULD BE LOWER CASE');
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entityId = $entity->getId();
        static::assertNotNull($entityId);

        $persistedEntity = $this->em->find(Issue196Entity::class, $entityId);
        static::assertNotNull($persistedEntity);

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $currentRevision = $auditReader->getCurrentRevision(Issue196Entity::class, $entityId);
        static::assertNotNull($currentRevision);
        $currentRevisionEntity = $auditReader->find(Issue196Entity::class, $entityId, $currentRevision);
        static::assertNotNull($currentRevisionEntity);

        static::assertSame(
            $persistedEntity->getSqlConversionField(),
            $currentRevisionEntity->getSqlConversionField(),
            'Current revision of audited entity is not equivalent to persisted entity:'
        );
    }
}
