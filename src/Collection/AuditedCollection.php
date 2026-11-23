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
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditReader;
use SimpleThings\EntityAudit\Exception\AuditedCollectionException;

/**
 * @phpstan-template TKey of array-key
 * @phpstan-template T of object
 * @phpstan-implements Collection<TKey, T>
 * @phpstan-implements Selectable<TKey, T>
 *
 * NEXT_MAJOR: Declare the class as final.
 *
 * @final since 1.19.0
 */
class AuditedCollection implements Collection, Selectable
{
    /**
     * @var AuditConfiguration
     */
    protected $configuration;

    /**
     * Entity collection. It can be empty if the collection has not been
     * initialized yet or contain identifiers to load the entities.
     *
     * @var Collection<int|string, array>
     *
     * @phpstan-var Collection<TKey, array{keys: array<string, int|string>, rev: string|int}>
     */
    protected $entities;

    /**
     * Loaded entity collection. It can be empty if the collection has not
     * been loaded yet or contain audited entities.
     *
     * @var Collection<int|string, object>
     *
     * @phpstan-var ArrayCollection<TKey, T>
     */
    protected $loadedEntities;

    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @param string                                  $class
     * @param array<string, mixed>|AssociationMapping $associationDefinition
     * @param array<string, mixed>                    $foreignKeys
     * @param string|int                              $revision
     *
     * @phpstan-param ClassMetadata<T> $metadata
     * @phpstan-param class-string<T> $class
     */
    public function __construct(
        protected AuditReader $auditReader,
        protected $class,
        protected ClassMetadata $metadata,
        protected array|AssociationMapping $associationDefinition,
        protected array $foreignKeys,
        protected $revision,
    ) {
        $this->configuration = $auditReader->getConfiguration();
        $this->entities = new ArrayCollection();
        $this->loadedEntities = new ArrayCollection();
    }

    public function add(mixed $element): void
    {
        throw new AuditedCollectionException('The AuditedCollection is read-only');
    }

    public function clear(): void
    {
        $this->entities = new ArrayCollection();
        $this->loadedEntities = new ArrayCollection();
        $this->initialized = false;
    }

    public function contains(mixed $element): bool
    {
        $this->forceLoad();

        return $this->loadedEntities->contains($element);
    }

    /**
     * @psalm-mutation-free See https://github.com/psalm/psalm-plugin-doctrine/issues/97
     */
    public function isEmpty(): bool
    {
        $this->initialize();

        return $this->entities->isEmpty();
    }

    /**
     * @param string|int $key
     *
     * @return T|null
     */
    public function remove($key): mixed
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    public function removeElement(mixed $element): bool
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * @param int|string $key
     *
     * @phpstan-param TKey $key
     */
    public function containsKey($key): bool
    {
        $this->initialize();

        return $this->entities->containsKey($key);
    }

    /**
     * @param string|int $key
     *
     * @return object
     *
     * @phpstan-param TKey $key
     * @phpstan-return T
     */
    public function get($key): mixed
    {
        return $this->offsetGet($key);
    }

    /**
     * @phpstan-return list<TKey>
     */
    public function getKeys(): array
    {
        $this->initialize();

        return $this->entities->getKeys();
    }

    /**
     * @return object[]
     *
     * @phpstan-return list<T>
     */
    public function getValues(): array
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
    public function toArray(): array
    {
        $this->forceLoad();

        return $this->loadedEntities->toArray();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function first(): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->first();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function last(): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->last();
    }

    /**
     * @return TKey|null
     */
    public function key(): int|string|null
    {
        $this->forceLoad();

        return $this->loadedEntities->key();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function current(): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->current();
    }

    /**
     * @return object|false
     *
     * @phpstan-return T|false
     */
    public function next(): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->next();
    }

    /**
     * @phpstan-param \Closure(TKey, T):bool $p
     */
    public function exists(\Closure $p): bool
    {
        $this->forceLoad();

        return $this->loadedEntities->exists($p);
    }

    /**
     * @return Collection<TKey, T>
     *
     * @phpstan-param \Closure(T, TKey):bool $p
     * @phpstan-return Collection<TKey, T>
     */
    public function filter(\Closure $p): Collection
    {
        $this->forceLoad();

        return $this->loadedEntities->filter($p);
    }

    /**
     * @phpstan-param \Closure(TKey, T):bool $p
     */
    public function forAll(\Closure $p): bool
    {
        $this->forceLoad();

        return $this->loadedEntities->forAll($p);
    }

    /**
     * @phpstan-template U
     *
     * @return Collection<TKey, U>
     *
     * @phpstan-param \Closure(T):U $func
     * @phpstan-return Collection<TKey, U>
     */
    public function map(\Closure $func): Collection
    {
        $this->forceLoad();

        return $this->loadedEntities->map($func);
    }

    /**
     * @return array<Collection<TKey, T>>
     *
     * @phpstan-param \Closure(TKey, T):bool $p
     * @phpstan-return array{0: Collection<TKey, T>, 1: Collection<TKey, T>}
     */
    public function partition(\Closure $p): array
    {
        $this->forceLoad();

        return $this->loadedEntities->partition($p);
    }

    /**
     * @return TKey|false
     */
    public function indexOf(mixed $element): int|string|false
    {
        $this->forceLoad();

        return $this->loadedEntities->indexOf($element);
    }

    /**
     * @param int      $offset
     * @param int|null $length
     *
     * @return object[]
     *
     * @phpstan-return array<TKey,T>
     */
    public function slice($offset, $length = null): array
    {
        $this->forceLoad();

        return $this->loadedEntities->slice($offset, $length);
    }

    /**
     * @return \Traversable<TKey, T>
     */
    public function getIterator(): \Traversable
    {
        $this->forceLoad();

        return $this->loadedEntities->getIterator();
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->forceLoad();

        return $this->loadedEntities->offsetExists($offset);
    }

    /**
     * @return object
     *
     * @phpstan-return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->loadedEntities->offsetExists($offset)) {
            $entity = $this->loadedEntities->offsetGet($offset);
            \assert(null !== $entity);

            return $entity;
        }

        $this->initialize();

        if (!$this->entities->offsetExists($offset)) {
            throw new AuditedCollectionException(\sprintf('Offset "%s" is not defined', $offset));
        }

        $entity = $this->entities->offsetGet($offset);
        \assert(null !== $entity);
        $resolvedEntity = $this->resolve($entity);
        $this->loadedEntities->offsetSet($offset, $resolvedEntity);

        return $resolvedEntity;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new AuditedCollectionException('AuditedCollection is read-only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new AuditedCollectionException('Audited collections does not support removal');
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        $this->initialize();

        return $this->entities->count();
    }

    /**
     * @return T|null
     *
     * @phpstan-return T|null
     */
    public function findFirst(\Closure $p): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->findFirst($p);
    }

    public function reduce(\Closure $func, mixed $initial = null): mixed
    {
        $this->forceLoad();

        return $this->loadedEntities->reduce($func, $initial);
    }

    /**
     * @return Collection<TKey, T>
     */
    public function matching(Criteria $criteria): Collection
    {
        $this->forceLoad();

        return $this->loadedEntities->matching($criteria);
    }

    /**
     * @param array{keys: array<string, int|string>, rev: string|int} $entity
     *
     * @return object
     *
     * @phpstan-return T
     */
    protected function resolve($entity)
    {
        $object = $this->auditReader->find(
            $this->class,
            $entity['keys'],
            $this->revision
        );

        if (null === $object) {
            throw new AuditedCollectionException('Cannot resolve the entity.');
        }

        return $object;
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

        /** @var array<array<string, int|string>> $rows */
        $rows = $this->auditReader->getConnection()->fetchAllAssociative($sql, $params);

        foreach ($rows as $row) {
            $entity = [
                'rev' => $row['rev'],
            ];

            unset($row['rev']);

            $entity['keys'] = $row;

            if (isset($this->associationDefinition['indexBy'])) {
                /** @var TKey $key */
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
