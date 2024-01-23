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
use SimpleThings\EntityAudit\Utils\ArrayDiff;

final class ArrayDiffTest extends TestCase
{
    public function testDiff(): void
    {
        $diff = new ArrayDiff();
        $array1 = ['one' => 'I', 'two' => '2'];
        $array2 = ['one' => 'I', 'two' => 'II'];
        $expected = ['one' => ['old' => '', 'new' => '', 'same' => 'I'], 'two' => ['old' => '2', 'new' => 'II', 'same' => '']];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffIsCaseSensitive(): void
    {
        $diff = new ArrayDiff();
        $array1 = ['one' => 'I', 'two' => 'ii'];
        $array2 = ['one' => 'I', 'two' => 'II'];
        $expected = ['one' => ['old' => '', 'new' => '', 'same' => 'I'], 'two' => ['old' => 'ii', 'new' => 'II', 'same' => '']];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffDate(): void
    {
        $diff = new ArrayDiff();

        $dateInstance1 = new \DateTimeImmutable('2014-01-01 00:00:00.000000');
        $dateInstance2 = new \DateTimeImmutable('2014-01-01 00:00:00.000000');

        $array1 = ['date' => $dateInstance1];
        $array2 = ['date' => $dateInstance2];
        $expected = ['date' => ['old' => '', 'new' => '', 'same' => $dateInstance1]];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffDateDifferent(): void
    {
        $diff = new ArrayDiff();

        $dateInstance1 = new \DateTimeImmutable('2014-01-01 00:00:00.000000');
        $dateInstance2 = new \DateTimeImmutable('2014-01-02 00:00:00.000000');

        $array1 = ['date' => $dateInstance1];
        $array2 = ['date' => $dateInstance2];
        $expected = ['date' => ['old' => $dateInstance1, 'new' => $dateInstance2, 'same' => '']];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffDateSameButTimezoneDifferent(): void
    {
        $diff = new ArrayDiff();

        $dateInstance1 = new \DateTimeImmutable('2014-01-01 00:00:00.000000', new \DateTimeZone('Europe/Luxembourg'));
        $dateInstance2 = new \DateTimeImmutable('2014-01-01 00:00:00.000000', new \DateTimeZone('UTC'));

        $array1 = ['date' => $dateInstance1];
        $array2 = ['date' => $dateInstance2];
        $expected = ['date' => ['old' => $dateInstance1, 'new' => $dateInstance2, 'same' => '']];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffObjectSame(): void
    {
        $diff = new ArrayDiff();
        $object1 = (object) ['one' => 'I', 'two' => 'II'];
        $object2 = (object) ['one' => 'I', 'two' => 'II'];
        $array1 = ['object' => $object1];
        $array2 = ['object' => $object2];
        $expected = ['object' => ['old' => '', 'new' => '', 'same' => $object1]];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }

    public function testDiffObjectDifferent(): void
    {
        $diff = new ArrayDiff();
        $object1 = (object) ['one' => 'I', 'two' => 'ii'];
        $object2 = (object) ['one' => 'I', 'two' => 'II'];
        $array1 = ['object' => $object1];
        $array2 = ['object' => $object2];
        $expected = ['object' => ['old' => $object1, 'new' => $object2, 'same' => '']];

        $result = $diff->diff($array1, $array2);

        static::assertSame($expected, $result);
    }
}
