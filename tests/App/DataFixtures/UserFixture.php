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

namespace Sonata\EntityAuditBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Sonata\EntityAuditBundle\Tests\App\Entity\User;

final class UserFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User('bob');

        $manager->persist($user);
        $manager->flush();

        $user->setName('alice');

        $manager->flush();
    }
}
