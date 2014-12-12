<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
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

namespace SimpleThings\EntityAudit\Collection;

use Closure;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;
use Traversable;

class AuditedCollection implements Collection
{
    /**
     * Related audit reader instance
     *
     * @var AuditReader
     */
    protected $auditReader;

    /**
     * Class to fetch
     * @var string
     */
    protected $class;

    /**
     * Foreign keys for target entity
     * @var array
     */
    protected $foreignKeys;

    /**
     * Maximum revision to fetch
     * @var string
     */
    protected $revision;

    /**
     * @var AuditConfiguration
     */
    protected $configuration;

    /**
     * @var ClassMetadataInfo
     */
    protected $metadata;

    /**
     * Entity array. If can be:
     * - empty, if the collection has not been initialized yet
     * - store entity
     * - contain audited entity
     *
     * @var array
     */
    protected $entities = array();

    /**
     * Definition of current association
     *
     * @var array
     */
    protected $associationDefinition = array();

    /**
     * @var bool
     */
    protected $initialized = false;

    public function __construct(AuditReader $auditReader, $class, ClassMetadataInfo $classMeta, array $associationDefinition, array $foreignKeys, $revision)
    {
        $this->auditReader = $auditReader;
        $this->class = $class;
        $this->foreignKeys = $foreignKeys;
        $this->revision = $revision;
        $this->configuration = $auditReader->getConfiguration();
        $this->metadata = $classMeta;
        $this->associationDefinition = $associationDefinition;
    }

    function add($element)
    {
        throw new AuditedCollectionException('The AuditedCollection is read-only');
    }

    /**
     * Clears the collection, removing all elements.
     *
     * @return void
     */
    function clear()
    {
        $this->entities = array();
        $this->initialized = false;
    }

    /**
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation, where n is the size of the collection.
     *
     * @param mixed $element The element to search for.
     *
     * @return boolean TRUE if the collection contains the element, FALSE otherwise.
     */
    function contains($element)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return boolean TRUE if the collection is empty, FALSE otherwise.
     */
    function isEmpty()
    {
        $this->initialize();
        return count($this->entities) == 0;
    }

    public function remove($key)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    public function removeElement($element)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * Checks whether the collection contains an element with the specified key/index.
     *
     * @param string|integer $key The key/index to check for.
     *
     * @return boolean TRUE if the collection contains an element with the specified key/index,
     *                 FALSE otherwise.
     */
    function containsKey($key)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Gets the element at the specified key/index.
     *
     * @param string|integer $key The key/index of the element to retrieve.
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Gets all keys/indices of the collection.
     *
     * @return array The keys/indices of the collection, in the order of the corresponding
     *               elements in the collection.
     */
    function getKeys()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Gets all values of the collection.
     *
     * @return array The values of all elements in the collection, in the order they
     *               appear in the collection.
     */
    function getValues()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Sets an element in the collection at the specified key/index.
     *
     * @param string|integer $key The key/index of the element to set.
     * @param mixed $value The element to set.
     *
     * @return void
     */
    function set($key, $value)
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return array
     */
    public function toArray()
    {
        $this->initialize();

        foreach ($this->entities as $key => $entity) {
            if (is_array($entity)) {
                $this->entities[$key] = $this->resolve($entity);
            }
        }

        return $this->entities;
    }

    /**
     * Sets the internal iterator to the first element in the collection and returns this element.
     *
     * @return mixed
     */
    function first()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Sets the internal iterator to the last element in the collection and returns this element.
     *
     * @return mixed
     */
    function last()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Gets the key/index of the element at the current iterator position.
     *
     * @return int|string
     */
    function key()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Gets the element of the collection at the current iterator position.
     *
     * @return mixed
     */
    function current()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Moves the internal iterator position to the next element and returns this element.
     *
     * @return mixed
     */
    function next()
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Tests for the existence of an element that satisfies the given predicate.
     *
     * @param Closure $p The predicate.
     *
     * @return boolean TRUE if the predicate is TRUE for at least one element, FALSE otherwise.
     */
    function exists(Closure $p)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Returns all the elements of this collection that satisfy the predicate p.
     * The order of the elements is preserved.
     *
     * @param Closure $p The predicate used for filtering.
     *
     * @return Collection A collection with the results of the filter operation.
     */
    function filter(Closure $p)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Tests whether the given predicate p holds for all elements of this collection.
     *
     * @param Closure $p The predicate.
     *
     * @return boolean TRUE, if the predicate yields TRUE for all elements, FALSE otherwise.
     */
    function forAll(Closure $p)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the elements returned by the function.
     *
     * @param Closure $func
     *
     * @return Collection
     */
    function map(Closure $func)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Partitions this collection in two collections according to a predicate.
     * Keys are preserved in the resulting collections.
     *
     * @param Closure $p The predicate on which to partition.
     *
     * @return array An array with two elements. The first element contains the collection
     *               of elements where the predicate returned TRUE, the second element
     *               contains the collection of elements where the predicate returned FALSE.
     */
    function partition(Closure $p)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Gets the index/key of a given element. The comparison of two elements is strict,
     * that means not only the value but also the type must match.
     * For objects this means reference equality.
     *
     * @param mixed $element The element to search for.
     *
     * @return int|string|bool The key/index of the element or FALSE if the element was not found.
     */
    function indexOf($element)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * Extracts a slice of $length elements starting at position $offset from the Collection.
     *
     * If $length is null it returns all elements from $offset to the end of the Collection.
     * Keys have to be preserved by this method. Calling this method will only return the
     * selected slice and NOT change the elements contained in the collection slice is called on.
     *
     * @param int $offset The offset to start from.
     * @param int|null $length The maximum number of elements to return, or null for no limit.
     *
     * @return array
     */
    function slice($offset, $length = null)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $this->initialize();

        //this call forcibly load all collection
        foreach ($this->entities as $key => $entity) {
            if (is_array($entity)) {
                $this->entities[$key] = $this->resolve($entity);
            }
        }

        return new \ArrayIterator($this->entities);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        throw new \Exception(__METHOD__ . ' is not yet implemented');
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        if (!isset($this->entities[$offset])) {
            throw new AuditedCollectionException(sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities[$offset];

        if (is_object($entity)) {
            return $entity;
        } else {
            return $this->entities[$offset] = $this->resolve($entity);
        }
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        $this->initialize();

        return count($this->entities);
    }

    protected function resolve($entity)
    {
        return $this->auditReader
            ->find(
                $this->class,
                $entity['keys'],
                $this->revision
            );
    }

    protected function initialize()
    {
        if (!$this->initialized) {
            $params = array();

            $sql = 'SELECT MAX('.$this->configuration->getRevisionFieldName().') as rev, ';
            $sql .= $this->configuration->getRevisionTypeFieldName().' AS revtype, ';
            $sql .= implode(', ', $this->metadata->getIdentifierColumnNames()).' ';
            if (isset($this->associationDefinition['indexBy'])) {
                $sql .= ', '.$this->associationDefinition['indexBy'].' ';
            }
            $sql .= 'FROM '.$this->configuration->getTablePrefix().$this->metadata->table['name'].$this->configuration->getTableSuffix().' t ';
            $sql .= 'WHERE '.$this->configuration->getRevisionFieldName().' <= '.$this->revision.' ';

            foreach ($this->foreignKeys as $column => $value) {
                $sql .= 'AND '.$column.' = ? ';
                $params[] = $value;
            }

            //we check for revisions greater than current belonging to other entities
            $sql .= 'AND NOT EXISTS (SELECT * FROM '.$this->configuration->getTablePrefix().$this->metadata->table['name'].$this->configuration->getTableSuffix().' st WHERE';

            //ids
            foreach ($this->metadata->getIdentifierColumnNames() as $name) {
                $sql .= ' st.'.$name.' = t.'.$name.' AND';
            }

            //foreigns
            $sql .= ' ((';

            //master entity query, not equals
            $notEqualParts = $nullParts = array();
            foreach($this->foreignKeys as $column => $value) {
                $notEqualParts[] = $column.' <> ?';
                $nullParts[] = $column.' IS NULL';
                $params[] = $value;
            }

            $sql .= implode(' AND ', $notEqualParts).') OR ('.implode(' AND ', $nullParts).'))';

            //revision
            $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' <= '.$this->revision;
            $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' > t.'.$this->configuration->getRevisionFieldName();

            $sql .= ') ';

            $sql .= 'GROUP BY '.implode(', ', $this->metadata->getIdentifierColumnNames()).' ';
            $sql .= 'HAVING '.$this->configuration->getRevisionTypeFieldName().' <> ?';
            //add rev type parameter
            $params[] = 'DEL';

            $rows = $this->auditReader->getConnection()->fetchAll($sql, $params);

            foreach ($rows as $row) {
                $entity = array(
                    'rev' => $row['rev'],
                    'revtype' => $row['revtype']
                );

                unset($row['rev'], $row['revtype']);

                $entity['keys'] = $row;

                if (isset($this->associationDefinition['indexBy'])) {
                    $key = $row[$this->associationDefinition['indexBy']];
                    unset($entity['keys'][$this->associationDefinition['indexBy']]);
                    $this->entities[$key] = $entity;
                } else {
                    $this->entities[] = $entity;
                }
            }

            //die($sql);
            /*
                                //todo: this should be checked with composite keys
                                $params = array();
                                $sql = 'SELECT MAX('.$this->config->getRevisionFieldName().') as rev, ';
                                $sql .= $this->config->getRevisionTypeFieldName().', ';
                                $sql .= implode(', ', $targetClass->getIdentifierColumnNames()).' ';
                                $sql .= $this->config->getTablePrefix().'FROM '.$targetClass->table['name'].$this->config->getTableSuffix().' ';
                                $sql .= 'WHERE '.$this->config->getRevisionFieldName().' <= '.$revision.' ';

                                //master entity query
                                foreach($targetClass->associationMappings[$assoc['mappedBy']]['sourceToTargetKeyColumns'] as $local => $foreign) {
                                    $sql .= 'AND '.$local.' = ? ';
                                    $field = $class->getFieldForColumn($foreign);
                                    $params[] = $class->reflFields[$field]->getValue($entity);
                                }

                                $sql .= 'GROUP BY '.implode(', ', $targetClass->getIdentifierColumnNames()).' ';
                                $sql .= 'HAVING '.$this->config->getRevisionTypeFieldName().' <> ?';
                                //add rev type parameter
                                $params[] = 'DEL';

                                $rows = $this->em->getConnection()->fetchAll($sql, $params);

                                $entities = array();

                                foreach ($rows as $row) {
                                    $pk = array();
                                    foreach ($targetClass->getIdentifierColumnNames() as $name) {
                                        $pk[$name] = $row[$name];
                                    }

                                    //if revison is smaller than requested revision it might be possible that the entity was moved to
                                    //another owner. we check this by finding entity with the same id but different foreign keys
                                    if ($row[$this->config->getRevisionFieldName()] != $revision) {
                                        $params = array();
                                        $sql = 'SELECT COUNT(*) AS cnt ';
                                        $sql .= $this->config->getTablePrefix().'FROM '.$targetClass->table['name'].$this->config->getTableSuffix().' ';
                                        $sql .= 'WHERE '.$this->config->getRevisionFieldName().' <= '.$revision;
                                        $sql .= ' AND '.$this->config->getRevisionFieldName().' > '.$row[$this->config->getRevisionFieldName()];

                                        foreach ($pk as $name => $value) {
                                            $sql .= (' AND ' . $name . ' = ?');
                                            $params[] = $value;
                                        }

                                        $sql .= ' AND ((';

                                        //master entity query, not equals
                                        $notEqualParts = $nullParts = array();
                                        foreach($targetClass->associationMappings[$assoc['mappedBy']]['sourceToTargetKeyColumns'] as $local => $foreign) {
                                            $notEqualParts[] = $local.' <> ?';
                                            $nullParts[] = $local.' IS NULL';
                                            $field = $class->getFieldForColumn($foreign);
                                            $params[] = $class->reflFields[$field]->getValue($entity);
                                        }

                                        $sql .= implode(' AND ', $notEqualParts).') OR ('.implode(' AND ', $nullParts).'))';

                                        $result = $this->em->getConnection()->fetchAll($sql, $params);

                                        $count = $result[0]['cnt'];

                                        if ($count > 0) {
                                            continue;
                                        }
                                    }

                                    $targetEntity = $this->find($targetClass->name, $pk, $revision);

                                    if (isset($assoc['indexBy'])) {
                                        $key = $targetClass->reflFields[$assoc['indexBy']]->getValue($targetEntity);
                                        $entities[$key] = $targetEntity;
                                    } else {
                                        $entities[] = $targetEntity;
                                    }
                                }*/
            $this->initialized = true;
        }
    }
}
