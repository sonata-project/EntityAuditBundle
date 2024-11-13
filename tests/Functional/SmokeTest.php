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

namespace Sonata\EntityAuditBundle\Tests\Functional;

use Sonata\EntityAuditBundle\Tests\App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SmokeTest extends WebTestCase
{
    /**
     * @dataProvider provideSuccessfulResponsesCases
     */
    public function testSuccessfulResponses(string $url): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, $url);

        static::assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<array{string}>
     */
    public function provideSuccessfulResponsesCases(): iterable
    {
        yield 'index' => ['/audit'];
        yield 'view revision' => ['/audit/viewrev/1'];

        $encodeUserClass = urlencode(User::class);

        yield 'view detail' => [\sprintf('/audit/viewent/%s/1', $encodeUserClass)];
        yield 'view entity' => [\sprintf('/audit/viewent/%s/1/1', $encodeUserClass)];
        yield 'compare' => [\sprintf('/audit/compare/%s/1?newRev=2&oldRev=1', $encodeUserClass)];
    }
}
