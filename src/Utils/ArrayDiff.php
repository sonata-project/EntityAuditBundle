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

            // If the values are objects, we will compare them by their properties.
            // This is necessary because the strict comparison operator (===) will return false if the objects are not the same instance.
            if ((\is_object($old) && \is_object($new) && $this->compareObjects($old, $new)) || ($old === $new)) {
                $row = ['old' => '', 'new' => '', 'same' => $old];
            } else {
                $row = ['old' => $old, 'new' => $new, 'same' => ''];
            }

            $diff[$field] = $row;
        }

        return $diff;
    }

    /**
     * Compare the type and the property values of two objects.
     * Return true if they are the same, false otherwise.
     * If the type is the same and all properties are the same, this will return true, even if they are not the same instance.
     * This method is different from comparing two objects using ==,
     * because internally the strict comparison operator (===) is used to compare the properties.
     *
     * @see https://www.php.net/manual/en/language.oop5.object-comparison.php
     */
    private function compareObjects(object $object1, object $object2): bool
    {
        // Check if the objects are of the same type.
        if ($object1::class !== $object2::class) {
            return false;
        }

        // Check if all properties are the same.
        $obj1Properties = (array) $object1;
        $obj2Properties = (array) $object2;
        foreach ($obj1Properties as $key => $value) {
            if (!\array_key_exists($key, $obj2Properties)) {
                return false;
            }
            if (\is_object($value) && \is_object($obj2Properties[$key])) {
                if (!$this->compareObjects($value, $obj2Properties[$key])) {
                    return false;
                }

                continue;
            }
            if ($value !== $obj2Properties[$key]) {
                return false;
            }
        }

        return true;
    }
}
