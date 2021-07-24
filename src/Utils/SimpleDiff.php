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
 * Class of the SimpleDiff PHP library by Paul Butler.
 *
 * @see https://github.com/paulgb/simplediff
 */
class SimpleDiff
{
    public function diff(array $old, array $new)
    {
        $maxlen = 0;
        $omax = 0;
        $nmax = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue, true);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                        $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if (0 === $maxlen) {
            return [['d' => $old, 'i' => $new]];
        }

        return array_merge(
            $this->diff(\array_slice($old, 0, $omax), \array_slice($new, 0, $nmax)),
            \array_slice($new, $nmax, $maxlen),
            $this->diff(\array_slice($old, $omax + $maxlen), \array_slice($new, $nmax + $maxlen))
        );
    }

    public function htmlDiff($old, $new)
    {
        $ret = '';
        $diff = $this->diff(explode(' ', $old), explode(' ', $new));
        foreach ($diff as $k) {
            if (\is_array($k)) {
                $ret .= (!empty($k['d']) ? '<del>'.implode(' ', $k['d']).'</del> ' : '').
                        (!empty($k['i']) ? '<ins>'.implode(' ', $k['i']).'</ins> ' : '');
            } else {
                $ret .= $k.' ';
            }
        }

        return $ret;
    }
}
