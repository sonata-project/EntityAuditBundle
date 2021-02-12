<?php

declare(strict_types=1);

namespace SimpleThings\EntityAudit\Tests\Utils;

use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\Utils\SimpleDiff;

class SimpleDiffTest extends TestCase
{
    /**
     * @dataProvider dataDiff
     *
     * @param $old
     * @param $new
     * @param $output
     */
    public function testDiff($old, $new, $output): void
    {
        $diff = new SimpleDiff();
        $d = $diff->htmlDiff($old, $new);

        $this->assertEquals($output, $d);
    }

    public static function dataDiff()
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
