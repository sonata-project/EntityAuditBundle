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

namespace SimpleThings\EntityAudit\Collection;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;

class AuditedCollection implements Collection
{
    /**
     * Related audit reader instance.
     *
     * @var AuditReader
     */
    protected $auditReader;

    /**
     * Class to fetch.
     *
     * @var string
     */
    protected $class;

    /**
     * Foreign keys for target entity.
     *
     * @var array
     */
    protected $foreignKeys;

    /**
     * Maximum revision to fetch.
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
     * - contain audited entity.
     *
     * @var array
     */
    protected $entities = [];

    /**
     * Definition of current association.
     *
     * @var array
     */
    protected $associationDefinition = [];

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

    /**
     * {@inheritdoc}
     */
    public function add($element): void
    {
        throw new AuditedCollectionException('The AuditedCollection is read-only');
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->entities = [];
        $this->initialized = false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        $this->forceLoad();

        return (bool) array_search($element, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->initialize();

        return 0 == \count($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): void
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element): void
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->initialize();

        return \array_key_exists($key, $this->entities);
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
    public function set($key, $value): void
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
    public function exists(\Closure $p)
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
    public function filter(\Closure $p)
    {
        $this->forceLoad();

        return array_filter($this->entities, $p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(\Closure $p)
    {
        $this->forceLoad();

        foreach ($this->entities as $entity) {
            if (!$p($entity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $func)
    {
        $this->forceLoad();

        return array_map($func, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $p)
    {
        $this->forceLoad();

        $true = $false = [];

        foreach ($this->entities as $entity) {
            if ($p($entity)) {
                $true[] = $entity;
            } else {
                $false[] = $entity;
            }
        }

        return [$true, $false];
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

        return \array_slice($this->entities, $offset, $length);
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

        return \array_key_exists($offset, $this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        if (!isset($this->entities[$offset])) {
            throw new AuditedCollectionException(sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities[$offset];

        if (\is_object($entity)) {
            return $entity;
        } else {
            return $this->entities[$offset] = $this->resolve($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->initialize();

        return \count($this->entities);
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

    protected function forceLoad(): void
    {
        $this->initialize();

        foreach ($this->entities as $key => $entity) {
            if (\is_array($entity)) {
                $this->entities[$key] = $this->resolve($entity);
            }
        }
    }

    protected function initialize(): void
    {
        if (!$this->initialized) {
            $params = [];

            $sql = 'SELECT MAX('.$this->configuration->getRevisionFieldName().') as rev, ';
            $sql .= implode(', ', $this->metadata->getIdentifierColumnNames()).' ';
            if (isset($this->associationDefinition['indexBy'])) {
                $sql .= ', '.$this->associationDefinition['indexBy'].' ';
            }
            $sql .= 'FROM '.$this->configuration->getTableName($this->metadata).' t ';
            $sql .= 'WHERE '.$this->configuration->getRevisionFieldName().' <= '.$this->revision.' ';

            foreach ($this->foreignKeys as $column => $value) {
                $sql .= 'AND '.$column.' = ? ';
                $params[] = $value;
            }

            //we check for revisions greater than current belonging to other entities
            $sql .= 'AND NOT EXISTS (SELECT * FROM '.$this->configuration->getTableName($this->metadata).' st WHERE';

            //ids
            foreach ($this->metadata->getIdentifierColumnNames() as $name) {
                $sql .= ' st.'.$name.' = t.'.$name.' AND';
            }

            //foreigns
            $sql .= ' ((';

            //master entity query, not equals
            $notEqualParts = $nullParts = [];
            foreach ($this->foreignKeys as $column => $value) {
                $notEqualParts[] = $column.' <> ?';
                $nullParts[] = $column.' IS NULL';
                $params[] = $value;
            }

            $sql .= implode(' AND ', $notEqualParts).') OR ('.implode(' AND ', $nullParts).'))';

            //revision
            $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' <= '.$this->revision;
            $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' > t.'.$this->configuration->getRevisionFieldName();

            $sql .= ') ';
            //end of check for for belonging to other entities

            //check for deleted revisions older than requested
            $sql .= 'AND NOT EXISTS (SELECT * FROM '.$this->configuration->getTableName($this->metadata).' sd WHERE';

            //ids
            foreach ($this->metadata->getIdentifierColumnNames() as $name) {
                $sql .= ' sd.'.$name.' = t.'.$name.' AND';
            }

            //revision
            $sql .= ' sd.'.$this->configuration->getRevisionFieldName().' <= '.$this->revision;
            $sql .= ' AND sd.'.$this->configuration->getRevisionFieldName().' > t.'.$this->configuration->getRevisionFieldName();

            $sql .= ' AND sd.'.$this->configuration->getRevisionTypeFieldName().' = ?';
            $params[] = 'DEL';

            $sql .= ') ';
            //end check for deleted revisions older than requested

            $sql .= 'AND '.$this->configuration->getRevisionTypeFieldName().' <> ? ';
            $params[] = 'DEL';

            $groupBy = $this->metadata->getIdentifierColumnNames();
            if (isset($this->associationDefinition['indexBy'])) {
                $groupBy[] = $this->associationDefinition['indexBy'];
            }
            $sql .= ' GROUP BY '.implode(', ', $groupBy);
            $sql .= ' ORDER BY '.implode(' ASC, ', $this->metadata->getIdentifierColumnNames()).' ASC';

            $rows = $this->auditReader->getConnection()->fetchAll($sql, $params);

            foreach ($rows as $row) {
                $entity = [
                    'rev' => $row['rev'],
                ];

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
    }
}
