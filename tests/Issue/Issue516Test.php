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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue516Entity;

final class Issue516Test extends BaseTest
{
    protected $schemaEntities = [
        Issue516Entity::class,
    ];

    protected $auditedEntities = [
        Issue516Entity::class,
    ];

    public function testIssue516(): void
    {
        $config = $this->auditManager->getConfiguration();
        $config->setGlobalIgnoreColumns(['color']);
        $auditReader = $this->auditManager->createAuditReader($this->em);

        $entity = (new Issue516Entity())
            ->setColor('blue')
            ->setHeight(182.4)
            ->setCreatedAt();

        $this->em->persist($entity);
        $this->em->flush();

        $auditBefore = $auditReader->find(
            Issue516Entity::class, $entity->getId(),
            $auditReader->getCurrentRevision(Issue516Entity::class, $entity->getId())
        );

        $entity->setColor('green');
        $this->em->flush();

        $auditAfter = $auditReader->find(
            Issue516Entity::class, $entity->getId(),
            $auditReader->getCurrentRevision(Issue516Entity::class, $entity->getId())
        );

        static::assertSame($auditBefore->getColor(), $auditAfter->getColor());
    }
}
