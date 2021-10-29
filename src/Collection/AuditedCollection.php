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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;

/**
 * @phpstan-template TKey of array-key
 * @phpstan-template T of object
 * @phpstan-implements Collection<TKey, T|array{keys: array, rev: string|int}>
 */
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
     *
     * @phpstan-var class-string<T>
     */
    protected $class;

    /**
     * Foreign keys for target entity.
     *
     * @var array<string, mixed>
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
     *
     * @phpstan-var ClassMetadataInfo<T> $metadata
     */
    protected $metadata;

    /**
     * Entity collection. If can be:
     * - empty, if the collection has not been initialized yet
     * - store entity
     * - contain audited entity.
     *
     * @var Collection<int|string, object|array<string, mixed>>
     *
     * @phpstan-var Collection<TKey, T|array{keys: array, rev: string|int}>
     */
    protected $entities;

    /**
     * Definition of current association.
     *
     * @var array<string, mixed>
     */
    protected $associationDefinition = [];

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param string               $class
     * @param array<string, mixed> $associationDefinition
     * @param array<string, mixed> $foreignKeys
     *
     * @phpstan-param class-string<T> $class
     * @phpstan-param ClassMetadataInfo<T> $classMeta
     */
    public function __construct(AuditReader $auditReader, $class, ClassMetadataInfo $classMeta, array $associationDefinition, array $foreignKeys, $revision)
    {
        $this->auditReader = $auditReader;
        $this->class = $class;
        $this->foreignKeys = $foreignKeys;
        $this->revision = $revision;
        $this->configuration = $auditReader->getConfiguration();
        $this->metadata = $classMeta;
        $this->associationDefinition = $associationDefinition;
        $this->entities = new ArrayCollection();
    }

    /**
     * @return bool
     */
    public function add($element)
    {
        throw new AuditedCollectionException('The AuditedCollection is read-only');
    }

    public function clear(): void
    {
        $this->entities = new ArrayCollection();
        $this->initialized = false;
    }

    /**
     * @return bool
     */
    public function contains($element)
    {
        $this->forceLoad();

        return $this->entities->contains($element);
    }

    /**
     * @return bool
     *
     * @psalm-mutation-free See https://github.com/psalm/psalm-plugin-doctrine/issues/97
     * @psalm-suppress ImpureMethodCall
     */
    public function isEmpty()
    {
        $this->initialize();

        return $this->entities->isEmpty();
    }

    /**
     * @return mixed
     */
    public function remove($key)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * @return bool
     */
    public function removeElement($element)
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * @return bool
     */
    public function containsKey($key)
    {
        $this->initialize();

        return $this->entities->containsKey($key);
    }

    /**
     * @return mixed
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        $this->initialize();

        return $this->entities->getKeys();
    }

    /**
     * @return array
     */
    public function getValues()
    {
        $this->forceLoad();

        return $this->entities->getValues();
    }

    public function set($key, $value): void
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $this->forceLoad();

        return $this->entities->toArray();
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $this->forceLoad();

        return $this->entities->first();
    }

    /**
     * @return mixed
     */
    public function last()
    {
        $this->forceLoad();

        return $this->entities->last();
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        $this->forceLoad();

        return $this->entities->key();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->forceLoad();

        return $this->entities->current();
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $this->forceLoad();

        return $this->entities->next();
    }

    /**
     * @return bool
     */
    public function exists(\Closure $p)
    {
        $this->forceLoad();

        return $this->entities->exists($p);
    }

    /**
     * @return Collection
     */
    public function filter(\Closure $p)
    {
        $this->forceLoad();

        return $this->entities->filter($p);
    }

    /**
     * @return bool
     */
    public function forAll(\Closure $p)
    {
        $this->forceLoad();

        return $this->entities->forAll($p);
    }

    /**
     * @return Collection
     */
    public function map(\Closure $func)
    {
        $this->forceLoad();

        return $this->entities->map($func);
    }

    /**
     * @return Collection
     *
     * @phpstan-return array{0: Collection<TKey, T|array{keys: array, rev: string|int}>, 1: Collection<TKey, T|array{keys: array, rev: string|int}>}
     */
    public function partition(\Closure $p)
    {
        $this->forceLoad();

        return $this->entities->partition($p);
    }

    /**
     * @return int|string|bool
     */
    public function indexOf($element)
    {
        $this->forceLoad();

        return $this->entities->indexOf($element);
    }

    /**
     * @return array
     */
    public function slice($offset, $length = null)
    {
        $this->forceLoad();

        return $this->entities->slice($offset, $length);
    }

    /**
     * @return \Traversable
     */
    public function getIterator()
    {
        $this->forceLoad();

        return $this->entities->getIterator();
    }

    /**
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->forceLoad();

        return $this->entities->offsetExists($offset);
    }

    /**
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->initialize();

        if (!$this->entities->offsetExists($offset)) {
            throw new AuditedCollectionException(sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities->offsetGet($offset);

        if (\is_object($entity)) {
            return $entity;
        }

        $resolvedEntity = $this->resolve($entity);
        $this->entities->offsetSet($offset, $resolvedEntity);

        return $resolvedEntity;
    }

    public function offsetSet($offset, $value): void
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    public function offsetUnset($offset): void
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * @return int
     */
    public function count()
    {
        $this->initialize();

        return $this->entities->count();
    }

    /**
     * @param array{keys: mixed} $entity
     */
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
            if (\is_array($entity) || $entity instanceof \ArrayAccess) {
                $this->entities->offsetSet($key, $this->resolve($entity));
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

            $rows = $this->auditReader->getConnection()->fetchAllAssociative($sql, $params);

            foreach ($rows as $row) {
                $entity = [
                    'rev' => $row['rev'],
                ];

                unset($row['rev']);

                $entity['keys'] = $row;

                if (isset($this->associationDefinition['indexBy'])) {
                    $key = $row[$this->associationDefinition['indexBy']];
                    unset($entity['keys'][$this->associationDefinition['indexBy']]);
                    $this->entities->offsetSet($key, $entity);
                } else {
                    $this->entities->add($entity);
                }
            }

            $this->initialized = true;
        }
    }
}
