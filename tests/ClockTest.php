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

use Psr\Clock\ClockInterface;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue318User;

final class ClockTest extends BaseTest
{
    protected $schemaEntities = [
        Issue318User::class,
    ];

    protected $auditedEntities = [
        Issue318User::class,
    ];

    public function testFixedClockIsUsed(): void
    {
        $em = $this->getEntityManager();

        $user = new Issue318User();
        $user->setAlias('alias');
        $em->persist($user);
        $em->flush();

        $userId = $user->getId();

        \assert(\is_int($userId));

        $reader = $this->getAuditManager()->createAuditReader($em);

        $revisions = $reader->findRevisions(Issue318User::class, $userId);

        static::assertCount(1, $revisions);
        static::assertSame($this->getFixedTime()->format('Y-m-d H:i:s'), $revisions[0]->getTimestamp()->format('Y-m-d H:i:s'));
    }

    protected function getClock(): ClockInterface
    {
        return new class($this->getFixedTime()) implements ClockInterface {
            public function __construct(private readonly \DateTimeImmutable $now)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return $this->now;
            }
        };
    }

    private function getFixedTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2022-10-25 15:00:00');
    }
}
