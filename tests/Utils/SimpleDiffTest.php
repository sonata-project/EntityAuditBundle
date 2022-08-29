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

namespace Sonata\EntityAuditBundle\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\Utils\SimpleDiff;

final class SimpleDiffTest extends TestCase
{
    /**
     * @dataProvider dataDiff
     */
    public function testDiff(string $old, string $new, string $output): void
    {
        $diff = new SimpleDiff();
        $d = $diff->htmlDiff($old, $new);

        static::assertSame($output, $d);
    }

    /**
     * @return iterable<array{string, string, string}>
     */
    public static function dataDiff(): iterable
    {
        return [
            ['Foo', 'foo', '<del>Foo</del> <ins>foo</ins> '],
            ['Foo Foo', 'Foo', 'Foo <del>Foo</del> '],
            ['Foo', 'Foo Foo', 'Foo <ins>Foo</ins> '],
            ['Foo Bar Baz', 'Foo Foo Foo', 'Foo <del>Bar Baz</del> <ins>Foo Foo</ins> '],
            ['Foo Bar Baz', 'Foo Baz', 'Foo <del>Bar</del> Baz '],
        ];
    }
}
