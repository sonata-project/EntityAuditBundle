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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\ReservedTableNameEntity;

final class IssueReservedTableNameTest extends BaseTestCase
{
    protected $schemaEntities = [
        ReservedTableNameEntity::class,
    ];

    protected $auditedEntities = [
        ReservedTableNameEntity::class,
    ];

    /**
     * On DBAL >= 4.3 the audit table name was derived from the *quoted* identifier
     * (e.g. `"user"` + `_audit` => `"user"_audit`), which is not a parseable name, so
     * schema generation crashed with "Object name has not been initialized".
     */
    public function testAuditSchemaForReservedKeywordTableName(): void
    {
        $entity = new ReservedTableNameEntity();
        $entity->setName('foo');

        $em = $this->getEntityManager();

        $em->persist($entity);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $id = $entity->getId();
        static::assertNotNull($id);

        $audited = $reader->find(ReservedTableNameEntity::class, $id, 1);
        static::assertSame('foo', $audited->getName());
    }
}
