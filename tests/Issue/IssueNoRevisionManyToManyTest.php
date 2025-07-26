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
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\Company;
use Sonata\EntityAuditBundle\Tests\Fixtures\Relation\DocumentType;

final class IssueNoRevisionManyToManyTest extends BaseTest
{
    protected $schemaEntities = [
        Company::class,
        DocumentType::class,
    ];

    protected $auditedEntities = [
        Company::class,
        DocumentType::class,
    ];

    public function testOwningSide(): void
    {
        $company = new Company();
        $company->setName('Company');
        $type = new DocumentType();
        $type->setName('Type');
        $company->addDocumentType($type);

        $em = $this->getEntityManager();

        $em->persist($company);
        $em->persist($type);
        $em->flush();

        $em->getConnection()->executeQuery('DELETE FROM DocumentType_audit');

        $manager = $this->getAuditManager();
        $reader = $manager->createAuditReader($em);
        $entity = $reader->find(Company::class, 1, 1);
        // Exception NoRevisionFoundException wasn't thrown
        static::assertInstanceOf(Company::class, $entity);
    }

    public function testInverseSide(): void
    {
        $company = new Company();
        $company->setName('Company');
        $type = new DocumentType();
        $type->setName('Type');
        $company->addDocumentType($type);

        $em = $this->getEntityManager();

        $em->persist($company);
        $em->persist($type);
        $em->flush();

        $em->getConnection()->executeQuery('DELETE FROM Company_audit');

        $manager = $this->getAuditManager();
        $reader = $manager->createAuditReader($em);
        $entity = $reader->find(DocumentType::class, 1, 1);
        // Exception NoRevisionFoundException wasn't thrown
        static::assertInstanceOf(DocumentType::class, $entity);
    }
}
