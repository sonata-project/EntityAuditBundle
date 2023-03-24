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
        $em = $this->getEntityManager();

        $reve = new Issue31Reve();
        $reve->setTitre('reve');

        $em->persist($reve);
        $em->flush();

        $user = new Issue31User();
        $user->setTitre('user');
        $user->setReve($reve);

        $em->persist($user);
        $em->flush();

        $em->remove($user);
        $em->flush();
    }
}
