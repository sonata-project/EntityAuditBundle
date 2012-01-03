<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit\Utils;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Creates a diff between persisted fields of 2 entities of the same type.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class EntityDiff extends SimpleDiff
{
    public function entityDiff(ClassMetadata $metadata, $oldEntity, $newEntity)
    {
        if (get_class($oldEntity) !== get_class($newEntity)) {
            throw new \InvalidArgumentException('Both entities must be of the same type');
        }

        $fields = $metadata->getFieldNames();
        sort($fields);

        $oldValues =
        $newValues = array();

        foreach ($fields AS $fieldName) {
            $oldValues[$fieldName] = $metadata->getFieldValue($oldEntity, $fieldName);
            $newValues[$fieldName] = $metadata->getFieldValue($newEntity, $fieldName);
        }

        $diff = $this->diff(array_values($oldValues), array_values($newValues));
        $diff = array_slice($diff, 1, -1);

        return array_combine($fields, $diff);
    }
}