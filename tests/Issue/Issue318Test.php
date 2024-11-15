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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue318User;

final class Issue318Test extends BaseTest
{
    protected $schemaEntities = [
        Issue318User::class,
    ];

    protected $auditedEntities = [
        Issue318User::class,
    ];

    public function testIssue318(): void
    {
        $user = new Issue318User();
        $user->setAlias('alias');

        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();
        $userMetadata = $em->getClassMetadata($user::class);
        $classes = [$userMetadata];
        $schema = $this->getSchemaTool()->getSchemaFromMetadata($classes);
        $schemaName = $schema->getName();
        $config = $this->getAuditManager()->getConfiguration();
        $userNotNullColumnName = 'alias';
        $userIdColumnName = 'id';
        $revisionsTableUser = $schema->getTable(\sprintf(
            '%s.%sissue318user%s',
            $schemaName,
            $config->getTablePrefix(),
            $config->getTableSuffix()
        ));

        static::assertFalse($revisionsTableUser->getColumn($userNotNullColumnName)->getNotnull());
        static::assertFalse($revisionsTableUser->getColumn($userIdColumnName)->getAutoincrement());
    }
}
