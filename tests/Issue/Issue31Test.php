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
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue31Reve;
use Sonata\EntityAuditBundle\Tests\Fixtures\Issue\Issue31User;

final class Issue31Test extends BaseTest
{
    protected $schemaEntities = [
        Issue31User::class,
        Issue31Reve::class,
    ];

    protected $auditedEntities = [
        Issue31User::class,
        Issue31Reve::class,
    ];

    /**
     * @doesNotPerformAssertions
     */
    public function testIssue31(): void
    {
        $reve = new Issue31Reve();
        $reve->setTitre('reve');

        $this->em->persist($reve);
        $this->em->flush();

        $user = new Issue31User();
        $user->setTitre('user');
        $user->setReve($reve);

        $this->em->persist($user);
        $this->em->flush();

        $this->em->remove($user);
        $this->em->flush();
    }
}
