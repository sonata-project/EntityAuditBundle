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
 * @phpstan-implements Collection<TKey, T>
 */
class AuditedCollection implements Collection
{
    /**
     * Related audit reader instance.
     *
     * @var AuditReader<T>
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
     * Entity collection. It can be empty if the collection has not been
     * initialized yet or contain identifiers to load the entities.
     *
     * @var Collection<int|string, array>
     * @phpstan-var Collection<TKey, array{keys: array, rev: string|int}>
     */
    protected $entities;

    /**
     * Loaded entity collection. It can be empty if the collection has not
     * been loaded yet or contain audited entities.
     *
     * @var Collection<int|string, object>
     * @phpstan-var Collection<TKey, T>
     */
    protected $loadedEntities;

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
     * @phpstan-param AuditReader<T> $auditReader
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
        $this->loadedEntities = new ArrayCollection();
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
        $this->loadedEntities = new ArrayCollection();
        $this->initialized = false;
    }

    /**
     * @return bool
     */
    public function contains($element)
    {
        $this->forceLoad();

        return $this->loadedEntities->contains($element);
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
     * @return object|null
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
     * @return object
     *
     * @phpstan-return T
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * @return array
     *
     * @phpstan-return array<TKey>
     */
    public function getKeys()
    {
        $this->initialize();

        return $this->entities->getKeys();
    }

    /**
     * @return object[]
     *
     * @phpstan-return array<T>
     */
    public function getValues()
    {
        $this->forceLoad();

        return $this->loadedEntities->getValues();
    }

    public function set($key, $value): void
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    /**
     * @return object[]
     *
     * @phpstan-return array<TKey, T>
     */
    public function toArray()
    {
        $this->forceLoad();

        return $this->loadedEntities->toArray();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function first()
    {
        $this->forceLoad();

        return $this->loadedEntities->first();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function last()
    {
        $this->forceLoad();

        return $this->loadedEntities->last();
    }

    /**
     * @return int|string|null
     */
    public function key()
    {
        $this->forceLoad();

        return $this->loadedEntities->key();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function current()
    {
        $this->forceLoad();

        return $this->loadedEntities->current();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function next()
    {
        $this->forceLoad();

        return $this->loadedEntities->next();
    }

    /**
     * @return bool
     *
     * @phpstan-param \Closure(TKey, T):bool $p
     */
    public function exists(\Closure $p)
    {
        $this->forceLoad();

        return $this->loadedEntities->exists($p);
    }

    /**
     * @return Collection
     *
     * @phpstan-param \Closure(T):bool $p
     * @phpstan-return Collection<TKey, T>
     */
    public function filter(\Closure $p)
    {
        $this->forceLoad();

        return $this->loadedEntities->filter($p);
    }

    /**
     * @return bool
     *
     * @phpstan-param \Closure(TKey, T):bool $p
     */
    public function forAll(\Closure $p)
    {
        $this->forceLoad();

        return $this->loadedEntities->forAll($p);
    }

    /**
     * @return Collection
     *
     * @phpstan-template R
     * @phpstan-param \Closure(T):R $func
     * @phpstan-return Collection<TKey, R>
     */
    public function map(\Closure $func)
    {
        $this->forceLoad();

        return $this->loadedEntities->map($func);
    }

    /**
     * @return Collection
     *
     * @phpstan-param \Closure(TKey, T):bool $p
     * @phpstan-return array{0: Collection<TKey, T>, 1: Collection<TKey, T>}
     */
    public function partition(\Closure $p)
    {
        $this->forceLoad();

        return $this->loadedEntities->partition($p);
    }

    /**
     * @return int|string|bool
     */
    public function indexOf($element)
    {
        $this->forceLoad();

        return $this->loadedEntities->indexOf($element);
    }

    /**
     * @return object[]
     *
     * @phpstan-return array<TKey,T>
     */
    public function slice($offset, $length = null)
    {
        $this->forceLoad();

        return $this->loadedEntities->slice($offset, $length);
    }

    /**
     * @return \Traversable
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        $this->forceLoad();

        return $this->loadedEntities->getIterator();
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $this->forceLoad();

        return $this->loadedEntities->offsetExists($offset);
    }

    /**
     * @return object
     *
     * @phpstan-return T
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if ($this->loadedEntities->offsetExists($offset)) {
            return $this->loadedEntities->offsetGet($offset);
        }

        $this->initialize();

        if (!$this->entities->offsetExists($offset)) {
            throw new AuditedCollectionException(sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities->offsetGet($offset);
        $resolvedEntity = $this->resolve($entity);
        $this->loadedEntities->offsetSet($offset, $resolvedEntity);

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
    #[\ReturnTypeWillChange]
    public function count()
    {
        $this->initialize();

        return $this->entities->count();
    }

    /**
     * @param array{keys: mixed} $entity
     *
     * @return object
     *
     * @phpstan-return T
     */
    protected function resolve($entity)
    {
        return $this->auditReader->find(
            $this->class,
            $entity['keys'],
            $this->revision
        );
    }

    protected function forceLoad(): void
    {
        $this->initialize();

        foreach ($this->entities as $key => $entity) {
            if (!$this->loadedEntities->offsetExists($key)) {
                $this->loadedEntities->offsetSet($key, $this->resolve($entity));
            }
        }
    }

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

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

        // we check for revisions greater than current belonging to other entities
        $sql .= 'AND NOT EXISTS (SELECT * FROM '.$this->configuration->getTableName($this->metadata).' st WHERE';

        // ids
        foreach ($this->metadata->getIdentifierColumnNames() as $name) {
            $sql .= ' st.'.$name.' = t.'.$name.' AND';
        }

        // foreigns
        $sql .= ' ((';

        // master entity query, not equals
        $notEqualParts = $nullParts = [];
        foreach ($this->foreignKeys as $column => $value) {
            $notEqualParts[] = $column.' <> ?';
            $nullParts[] = $column.' IS NULL';
            $params[] = $value;
        }

        $sql .= implode(' AND ', $notEqualParts).') OR ('.implode(' AND ', $nullParts).'))';

        // revision
        $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' <= '.$this->revision;
        $sql .= ' AND st.'.$this->configuration->getRevisionFieldName().' > t.'.$this->configuration->getRevisionFieldName();

        $sql .= ') ';
        // end of check for for belonging to other entities

        // check for deleted revisions older than requested
        $sql .= 'AND NOT EXISTS (SELECT * FROM '.$this->configuration->getTableName($this->metadata).' sd WHERE';

        // ids
        foreach ($this->metadata->getIdentifierColumnNames() as $name) {
            $sql .= ' sd.'.$name.' = t.'.$name.' AND';
        }

        // revision
        $sql .= ' sd.'.$this->configuration->getRevisionFieldName().' <= '.$this->revision;
        $sql .= ' AND sd.'.$this->configuration->getRevisionFieldName().' > t.'.$this->configuration->getRevisionFieldName();

        $sql .= ' AND sd.'.$this->configuration->getRevisionTypeFieldName().' = ?';
        $params[] = 'DEL';

        $sql .= ') ';
        // end check for deleted revisions older than requested

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
