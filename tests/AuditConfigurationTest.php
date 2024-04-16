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

namespace Sonata\EntityAuditBundle\Tests;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditConfiguration;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\ArticleAudit;

final class AuditConfigurationTest extends TestCase
{
    public function testItReturnsCorrectTableNameWhenTableSchemaIsNull(): void
    {
        $auditConfig = new AuditConfiguration();

        $metadata = new ClassMetadata(ArticleAudit::class);
        $metadata->setPrimaryTable([
            'name' => 'foo',
            'schema' => null,
        ]);

        static::assertSame('foo_audit', $auditConfig->getTableName($metadata));
    }

    public function testItReturnsCorrectTableNameWhenTableSchemaIsEmptyString(): void
    {
        $auditConfig = new AuditConfiguration();

        $metadata = new ClassMetadata(ArticleAudit::class);
        $metadata->setPrimaryTable([
            'name' => 'foo',
            'schema' => '',
        ]);

        static::assertSame('foo_audit', $auditConfig->getTableName($metadata));
    }

    public function testItReturnsCorrectTableNameWhenTableSchemaIsSet(): void
    {
        $auditConfig = new AuditConfiguration();

        $metadata = new ClassMetadata(ArticleAudit::class);
        $metadata->setPrimaryTable([
            'name' => 'foo',
            'schema' => 'xyz',
        ]);

        static::assertSame('xyz.foo_audit', $auditConfig->getTableName($metadata));
    }
}
