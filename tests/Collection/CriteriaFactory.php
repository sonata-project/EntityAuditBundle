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

namespace Sonata\EntityAuditBundle\Tests\Collection;

use Composer\InstalledVersions;
use Doctrine\Common\Collections\Criteria;

final class CriteriaFactory
{
    public static function create(): Criteria
    {
        $version = InstalledVersions::getVersion('doctrine/collections') ?? '';

        if (version_compare($version, '2.4.0', '>=') && version_compare($version, '3.0.0', '<')) {
            // This constructor argument was removed in Collections 3.
            return (new \ReflectionClass(Criteria::class))->newInstanceArgs(['firstResult' => 0, 'accessRawFieldValues' => true]);
        }

        return Criteria::create();
    }

    public static function descendingOrder(): mixed
    {
        if (version_compare(InstalledVersions::getVersion('doctrine/collections') ?? '', '3.1.0', '>=')) {
            return \SortDirection::Descending;
        }

        if (enum_exists(\Doctrine\Common\Collections\Order::class)) {
            return \Doctrine\Common\Collections\Order::Descending;
        }

        return 'DESC';
    }
}
