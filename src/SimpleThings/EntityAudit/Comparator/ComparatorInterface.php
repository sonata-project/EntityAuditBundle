<?php

namespace SimpleThings\EntityAudit\Comparator;

/**
 * Interface ComparatorInterface
 *
 * canCompare is always called before compare with the field and class mappings in case that data
 * is needed for the comparison it can be stored and used in the compare calculation
 *
 * @package SimpleThings\EntityAudit\Comparator
 */
interface ComparatorInterface
{
    /**
     * Given the new and old value return a boolean.
     * true if the change warrants a revision
     * false if the change does not warrant a revision
     *
     * @param $fieldName
     * @param $newValue
     * @param $oldValue
     * @return boolean
     */
    public function compare($fieldName, $newValue, $oldValue);

    /**
     * This gives you the data you need to make a decision on whether this class can compare the given values
     *
     * @param  ClassMetadata $classMetadata
     * @param  array         $fieldMapping
     * @param  string        $fieldName
     * @return boolean
     */
    public function canCompare($classMetadata, $fieldMapping, $fieldName);
}
