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

use Doctrine\Common\Collections\Collection;
use SimpleThings\EntityAudit\Tests\BaseTest;
use SimpleThings\EntityAudit\Tests\Fixtures\Issue\Issue308User;

final class Issue308Test extends BaseTest
{
    protected $schemaEntities = [
        Issue308User::class,
    ];

    protected $auditedEntities = [
        Issue308User::class,
    ];

    public function testIssue308(): void
    {
        $user = new Issue308User();
        $child1 = new Issue308User();
        $user->addChild($child1);
        $this->em->persist($child1);
        $this->em->persist($user);
        $this->em->flush();

        static::assertInstanceOf(Collection::class, $user->getChildren());

        $auditReader = $this->auditManager->createAuditReader($this->em);
        $auditReader->setLoadAuditedCollections(true);
        $userClass = \get_class($user);
        $revisions = $auditReader->findRevisions($userClass, $user->getId());
        static::assertCount(1, $revisions);
        $revision = reset($revisions);
        $userId = $user->getId();
        static::assertNotNull($userId);
        $auditedUser = $auditReader->find($userClass, ['id' => $userId], $revision->getRev());

        static::assertInstanceOf(Collection::class, $auditedUser->getChildren());
    }
}
