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

namespace SimpleThings\EntityAudit\Utils;

/**
 * Creates a diff between 2 arrays.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class ArrayDiff
{
    /**
     * @param mixed[] $oldData
     * @param mixed[] $newData
     *
     * @return array<string, array<string, mixed>>
     *
     * @phpstan-return array<string, array{old: mixed, new: mixed, same: mixed}>
     */
    public function diff($oldData, $newData)
    {
        $diff = [];

        $keys = array_keys($oldData + $newData);
        foreach ($keys as $field) {
            $old = \array_key_exists($field, $oldData) ? $oldData[$field] : null;
            $new = \array_key_exists($field, $newData) ? $newData[$field] : null;

            if ($old === $new) {
                $row = ['old' => '', 'new' => '', 'same' => $old];
            } else {
                $row = ['old' => $old, 'new' => $new, 'same' => ''];
            }

            $diff[$field] = $row;
        }

        return $diff;
    }
}
