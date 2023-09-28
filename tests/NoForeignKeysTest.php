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

use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use Sonata\EntityAuditBundle\Tests\Fixtures\Core\UserAudit;

final class NoForeignKeysTest extends BaseTest
{
    protected $schemaEntities = [
        UserAudit::class,
    ];

    protected $auditedEntities = [
        UserAudit::class,
    ];

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws NotAuditedException
     * @throws Exception
     */
    public function testRevisionForeignKeys(): void
    {
        $em = $this->getEntityManager();

        $user = new UserAudit('phansys');

        $em->persist($user);
        $em->flush();

        $reader = $this->getAuditManager()->createAuditReader($em);

        $userId = $user->getId();
        static::assertNotNull($userId);

        $revisions = $reader->findRevisions(UserAudit::class, $userId);

        static::assertCount(1, $revisions);

        $revision = $reader->getCurrentRevision(UserAudit::class, $userId);
        static::assertSame('1', (string) $revision);

        $revisionsTableName = $this->getAuditManager()->getConfiguration()->getRevisionTableName();

        $em->getConnection()->delete($revisionsTableName, ['id' => $revision]);
    }

    protected function getAuditManager(): AuditManager
    {
        if (null !== $this->auditManager) {
            return $this->auditManager;
        }

        $auditConfig = AuditConfiguration::forEntities($this->auditedEntities);
        $auditConfig->setGlobalIgnoreColumns(['ignoreme']);
        $auditConfig->setDisabledForeignKeys(true);
        $auditConfig->setUsernameCallable(static fn (): string => 'beberlei');

        $this->auditManager = new AuditManager($auditConfig, $this->getClock());
        $this->auditManager->registerEvents($this->getEntityManager()->getEventManager());

        return $this->auditManager;
    }
}
