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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;

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
     *
     * @var string
     */
    protected $class;

    /**
     * Foreign keys for target entity
     *
     * @var array
     */
    protected $foreignKeys;

    /**
     * Maximum revision to fetch
     *
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

    /**
     * @param AuditReader       $auditReader
     * @param ClassMetadataInfo $classMetadata
     * @param array             $associationDefinition
     * @param array             $foreignKeys
     * @param int               $revision
     */
    public function __construct(
        AuditReader $auditReader,
        ClassMetadataInfo $classMetadata,
        array $associationDefinition,
        array $foreignKeys,
        $revision
    ) {
        $this->auditReader = $auditReader;
        $this->class = $classMetadata->name;
        $this->foreignKeys = $foreignKeys;
        $this->revision = $revision;
        $this->configuration = $auditReader->getConfiguration();
        $this->metadata = $classMetadata;
        $this->associationDefinition = $associationDefinition;
    }

    /**
     * {@inheritdoc}
     */
    public function add($element)
    {
        throw new AuditedCollectionException('The AuditedCollection is read-only');
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->entities = array();
        $this->initialized = false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        $this->forceLoad();

        return (bool)array_search($element, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->initialize();

        return count($this->entities) == 0;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->initialize();

        return array_key_exists($key, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        $this->initialize();

        return array_keys($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $this->forceLoad();

        return array_values($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->forceLoad();

        return $this->entities;
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        $this->forceLoad();

        return reset($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        $this->forceLoad();

        return end($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $this->forceLoad();

        return key($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->forceLoad();

        return current($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->forceLoad();

        return next($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Closure $p)
    {
        $this->forceLoad();

        foreach ($this->entities as $entity) {
            if ($p($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        $this->forceLoad();

        return array_filter($this->entities, $p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(Closure $p)
    {
        $this->forceLoad();

        foreach ($this->entities as $entity) {
            if (! $p($entity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function map(Closure $func)
    {
        $this->forceLoad();

        return array_map($func, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(Closure $p)
    {
        $this->forceLoad();

        $true = $false = array();

        foreach ($this->entities as $entity) {
            if ($p($entity)) {
                $true[] = $entity;
            } else {
                $false[] = $entity;
            }
        }

        return array($true, $false);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        $this->forceLoad();

        return array_search($element, $this->entities, true);
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null)
    {
        $this->forceLoad();

        return array_slice($this->entities, $offset, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->forceLoad();

        return new \ArrayIterator($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->forceLoad();

        return array_key_exists($offset, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        if (! isset($this->entities[$offset])) {
            throw new AuditedCollectionException(sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities[$offset];

        if (is_object($entity)) {
            return $entity;
        }

        return $this->entities[$offset] = $this->resolve($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->initialize();

        return count($this->entities);
    }

    protected function resolve($entity)
    {
        return $this->auditReader->find($this->class, $entity['keys'], $this->revision);
    }

    protected function forceLoad()
    {
        $this->initialize();

        foreach ($this->entities as $key => $entity) {
            if (is_array($entity)) {
                $this->entities[$key] = $this->resolve($entity);
            }
        }
    }

    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $revisionFieldName = $this->configuration->getRevisionFieldName();
        $identifierColumnNames = $this->metadata->getIdentifierColumnNames();
        $connection = $this->auditReader->getConnection();

        $queryBuilder = $connection->createQueryBuilder()
            ->select(sprintf('MAX(%s) as rev', $revisionFieldName))
            ->addSelect($identifierColumnNames)
            ->from($this->configuration->getTableName($this->metadata), 't');

        $queryBuilder->where(sprintf(
            '%s <= %s',
            $revisionFieldName,
            $queryBuilder->createPositionalParameter($this->revision)
        ));

        if (isset($this->associationDefinition['indexBy'])) {
            $queryBuilder->addSelect($this->associationDefinition['indexBy']);
        }

        foreach ($this->foreignKeys as $column => $value) {
            $queryBuilder->andWhere($column . ' = ' . $queryBuilder->createPositionalParameter($value));
        }

        //we check for revisions greater than current belonging to other entities
        $belongingToEntitiesQB = $this->createBelongingToOtherEntitiesQueryBuilder($connection, $queryBuilder);
        $queryBuilder->andWhere(sprintf('NOT EXISTS(%s)', $belongingToEntitiesQB->getSQL()));

        //check for deleted revisions older than requested
        $deletedRevisionQB = $this->createDeletedRevisionsQueryBuilder($connection, $queryBuilder);
        $queryBuilder->andWhere(sprintf('NOT EXISTS(%s)', $deletedRevisionQB->getSQL()));

        $queryBuilder->andWhere(sprintf(
            '%s <> %s',
            $this->configuration->getRevisionTypeFieldName(),
            $queryBuilder->createPositionalParameter('DEL')
        ));

        $groupBy = $identifierColumnNames;
        if (isset($this->associationDefinition['indexBy'])) {
            $groupBy[] = $this->associationDefinition['indexBy'];
        }

        $queryBuilder->groupBy($groupBy);

        foreach ($identifierColumnNames as $identifierColumnName) {
            $queryBuilder->addOrderBy($identifierColumnName, 'ASC');
        }

        $rows = $queryBuilder->execute()->fetchAll();

        foreach ($rows as $row) {
            $entity = array('rev' => $row['rev']);
            unset($row['rev']);

            $entity['keys'] = $row;

            if (isset($this->associationDefinition['indexBy'])) {
                $key = $row[$this->associationDefinition['indexBy']];
                unset($entity['keys'][$this->associationDefinition['indexBy']]);
                $this->entities[$key] = $entity;
            } else {
                $this->entities[] = $entity;
            }
        }

        $this->initialized = true;
    }

    /**
     * @param Connection   $connection
     * @param QueryBuilder $parentQueryBuilder
     *
     * @return QueryBuilder
     */
    private function createBelongingToOtherEntitiesQueryBuilder(
        Connection $connection,
        QueryBuilder $parentQueryBuilder
    ) {
        $revisionFieldName = $this->configuration->getRevisionFieldName();
        $identifierColumnNames = $this->metadata->getIdentifierColumnNames();

        $queryBuilder = $connection->createQueryBuilder()
            ->select('1')
            ->from($this->configuration->getTableName($this->metadata), 'st')
            ->andWhere(sprintf('st.%1$s > t.%1$s', $revisionFieldName));

        $queryBuilder->andWhere(sprintf(
            'st.%s <= %s',
            $revisionFieldName,
            $parentQueryBuilder->createPositionalParameter($this->revision)
        ));

        //ids
        foreach ($identifierColumnNames as $name) {
            $queryBuilder->andWhere(sprintf('st.%1$s = t.%1$s', $name));
        }

        //master entity query, not equals
        $notEqualParts = $nullParts = array();
        foreach ($this->foreignKeys as $column => $value) {
            $notEqualParts[] = $column . ' <> ' . $parentQueryBuilder->createPositionalParameter($value);
            $nullParts[] = $column . ' IS NULL';
        }

        $expr = $queryBuilder->expr();
        $queryBuilder->andWhere(
            $expr->orX(
                $expr->andX(...$notEqualParts),
                $expr->andX(...$nullParts)
            )
        );

        return $queryBuilder;
    }

    /**
     * @param Connection   $connection
     * @param QueryBuilder $parentQueryBuilder
     *
     * @return QueryBuilder
     */
    private function createDeletedRevisionsQueryBuilder(Connection $connection, QueryBuilder $parentQueryBuilder)
    {
        $tableName = $this->configuration->getTableName($this->metadata);
        $revisionFieldName = $this->configuration->getRevisionFieldName();
        $identifierColumnNames = $this->metadata->getIdentifierColumnNames();

        $queryBuilder = $connection->createQueryBuilder()
            ->select('1')
            ->from($tableName, 'sd')
            ->andWhere(sprintf('sd.%1$s > t.%1$s', $revisionFieldName));

        $queryBuilder->andWhere(sprintf(
            'sd.%s <= %s',
            $revisionFieldName,
            $parentQueryBuilder->createPositionalParameter($this->revision)
        ));
        $queryBuilder->andWhere(sprintf(
            'sd.%s = %s',
            $this->configuration->getRevisionTypeFieldName(),
            $parentQueryBuilder->createPositionalParameter('DEL')
        ));

        //ids
        foreach ($identifierColumnNames as $name) {
            $queryBuilder->andWhere(sprintf('sd.%1$s = t.%1$s', $name));
        }

        return $queryBuilder;
    }
}
