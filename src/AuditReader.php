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

namespace SimpleThings\EntityAudit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use SimpleThings\EntityAudit\Collection\AuditedCollection;
use SimpleThings\EntityAudit\Exception\DeletedException;
use SimpleThings\EntityAudit\Exception\InvalidRevisionException;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\ArrayDiff;
use SimpleThings\EntityAudit\Utils\SQLResultCasing;

class AuditReader
{
    use SQLResultCasing;

    private AbstractPlatform $platform;

    private QuoteStrategy $quoteStrategy;

    /**
     * Entity cache to prevent circular references.
     *
     * @var array<string, array<string, array<int|string, object>>>
     *
     * @phpstan-var array<class-string, array<string, array<int|string, object>>>
     */
    private array $entityCache = [];

    /**
     * Decides if audited ToMany collections are loaded.
     */
    private bool $loadAuditedCollections = true;

    /**
     * Decides if audited ToOne collections are loaded.
     */
    private bool $loadAuditedEntities = true;

    /**
     * Decides if native (not audited) ToMany collections are loaded.
     */
    private bool $loadNativeCollections = true;

    /**
     * Decides if native (not audited) ToOne collections are loaded.
     */
    private bool $loadNativeEntities = true;

    public function __construct(
        private EntityManagerInterface $em,
        private AuditConfiguration $config,
        private MetadataFactory $metadataFactory
    ) {
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * @return bool
     */
    public function isLoadAuditedCollections()
    {
        return $this->loadAuditedCollections;
    }

    /**
     * @param bool $loadAuditedCollections
     */
    public function setLoadAuditedCollections($loadAuditedCollections): void
    {
        $this->loadAuditedCollections = $loadAuditedCollections;
    }

    /**
     * @return bool
     */
    public function isLoadAuditedEntities()
    {
        return $this->loadAuditedEntities;
    }

    /**
     * @param bool $loadAuditedEntities
     */
    public function setLoadAuditedEntities($loadAuditedEntities): void
    {
        $this->loadAuditedEntities = $loadAuditedEntities;
    }

    /**
     * @return bool
     */
    public function isLoadNativeCollections()
    {
        return $this->loadNativeCollections;
    }

    /**
     * @param bool $loadNativeCollections
     */
    public function setLoadNativeCollections($loadNativeCollections): void
    {
        $this->loadNativeCollections = $loadNativeCollections;
    }

    /**
     * @return bool
     */
    public function isLoadNativeEntities()
    {
        return $this->loadNativeEntities;
    }

    /**
     * @param bool $loadNativeEntities
     */
    public function setLoadNativeEntities($loadNativeEntities): void
    {
        $this->loadNativeEntities = $loadNativeEntities;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->em->getConnection();
    }

    /**
     * @return AuditConfiguration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Clears entity cache. Call this if you are fetching subsequent revisions using same AuditManager.
     */
    public function clearEntityCache(): void
    {
        $this->entityCache = [];
    }

    /**
     * Find a class at the specific revision.
     *
     * This method does not require the revision to be exact but it also searches for an earlier revision
     * of this entity and always returns the latest revision below or equal the given revision. Commonly, it
     * returns last revision INCLUDING "DEL" revision. If you want to throw exception instead, set
     * $threatDeletionAsException to true.
     *
     * @template T of object
     *
     * @param string                                    $className
     * @param int|string|array<string, int|string>      $id
     * @param array{threatDeletionsAsExceptions?: bool} $options
     *
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws Exception
     * @throws ORMException
     * @throws \RuntimeException
     *
     * @return object|null
     *
     * @phpstan-param class-string<T>                   $className
     * @phpstan-return T|null
     */
    public function find($className, int|string|array $id, int|string $revision, array $options = [])
    {
        $options = array_merge(['threatDeletionsAsExceptions' => false], $options);

        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        /** @var ClassMetadata<T> $classMetadata */
        $classMetadata = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($classMetadata);

        $whereSQL = 'e.'.$this->config->getRevisionFieldName().' <= ?';

        foreach ($classMetadata->identifier as $idField) {
            if (\is_array($id) && \count($id) > 0) {
                $idKeys = array_keys($id);
                $columnName = $idKeys[0];
            } elseif (isset($classMetadata->fieldMappings[$idField])) {
                $columnName = $classMetadata->fieldMappings[$idField]['columnName'];
            } elseif (isset($classMetadata->associationMappings[$idField]['joinColumns'])) {
                $columnName = $classMetadata->associationMappings[$idField]['joinColumns'][0]['name'];
            } else {
                throw new \RuntimeException('column name not found  for'.$idField);
            }

            $whereSQL .= ' AND e.'.$columnName.' = ?';
        }

        if (!\is_array($id)) {
            $id = [$classMetadata->identifier[0] => $id];
        }

        $columnList = ['e.'.$this->config->getRevisionTypeFieldName()];
        $columnMap = [];

        foreach ($classMetadata->fieldNames as $columnName => $field) {
            $tableAlias = $classMetadata->isInheritanceTypeJoined()
                && $classMetadata->isInheritedField($field)
                && !$classMetadata->isIdentifier($field)
                    ? 're' // root entity
                    : 'e';

            $type = Type::getType($classMetadata->fieldMappings[$field]['type']);
            $columnList[] = sprintf(
                '%s AS %s',
                $type->convertToPHPValueSQL(
                    $tableAlias.'.'.$this->quoteStrategy->getColumnName($field, $classMetadata, $this->platform),
                    $this->platform
                ),
                $this->platform->quoteSingleIdentifier($field)
            );
            $columnMap[$field] = $this->getSQLResultCasing($this->platform, $columnName);
        }

        foreach ($classMetadata->associationMappings as $assoc) {
            if (
                ($assoc['type'] & ClassMetadata::TO_ONE) === 0
                || false === $assoc['isOwningSide']
                || !isset($assoc['joinColumnFieldNames'])
            ) {
                continue;
            }

            foreach ($assoc['joinColumnFieldNames'] as $sourceCol) {
                $tableAlias = $classMetadata->isInheritanceTypeJoined()
                    && $classMetadata->isInheritedAssociation($assoc['fieldName'])
                    && !$classMetadata->isIdentifier($assoc['fieldName'])
                        ? 're' // root entity
                        : 'e';
                $columnList[] = $tableAlias.'.'.$sourceCol;
                $columnMap[$sourceCol] = $this->getSQLResultCasing($this->platform, $sourceCol);
            }
        }

        $joinSql = '';
        if ($classMetadata->isInheritanceTypeJoined() && $classMetadata->name !== $classMetadata->rootEntityName) {
            $rootClass = $this->em->getClassMetadata($classMetadata->rootEntityName);
            $rootTableName = $this->config->getTableName($rootClass);
            $joinSql = "INNER JOIN {$rootTableName} re ON";
            $joinSql .= ' re.'.$this->config->getRevisionFieldName().' = e.'.$this->config->getRevisionFieldName();
            foreach ($classMetadata->getIdentifierColumnNames() as $name) {
                $joinSql .= " AND re.$name = e.$name";
            }
        }

        $values = [...[$revision], ...array_values($id)];

        if (
            !$classMetadata->isInheritanceTypeNone()
            && null !== $classMetadata->discriminatorColumn
        ) {
            $columnList[] = $classMetadata->discriminatorColumn['name'];
            if ($classMetadata->isInheritanceTypeSingleTable()
                && null !== $classMetadata->discriminatorValue) {
                // Support for single table inheritance sub-classes
                $allDiscrValues = array_flip($classMetadata->discriminatorMap);
                $queriedDiscrValues = [$this->em->getConnection()->quote($classMetadata->discriminatorValue)];
                foreach ($classMetadata->subClasses as $subclassName) {
                    $queriedDiscrValues[] = $this->em->getConnection()->quote($allDiscrValues[$subclassName]);
                }

                $whereSQL .= sprintf(
                    ' AND %s IN (%s)',
                    $classMetadata->discriminatorColumn['name'],
                    implode(', ', $queriedDiscrValues)
                );
            }
        }

        $query = sprintf(
            'SELECT %s FROM %s e %s WHERE %s ORDER BY e.%s DESC',
            implode(', ', $columnList),
            $tableName,
            $joinSql,
            $whereSQL,
            $this->config->getRevisionFieldName()
        );

        $row = $this->em->getConnection()->fetchAssociative($query, $values);

        if (false === $row) {
            throw new NoRevisionFoundException($classMetadata->name, $id, $revision);
        }

        if ($options['threatDeletionsAsExceptions'] && 'DEL' === $row[$this->config->getRevisionTypeFieldName()]) {
            throw new DeletedException($classMetadata->name, $id, $revision);
        }

        unset($row[$this->config->getRevisionTypeFieldName()]);

        return $this->createEntity($classMetadata->name, $columnMap, $row, $revision);
    }

    /**
     * NEXT_MAJOR: Change the default value to `null`.
     *
     * Return a list of all revisions.
     *
     * @param int|null $limit
     * @param int      $offset
     *
     * @throws Exception
     *
     * @return Revision[]
     */
    public function findRevisionHistory($limit = 20, $offset = 0)
    {
        $query = $this->platform->modifyLimitQuery(
            'SELECT * FROM '.$this->config->getRevisionTableName().' ORDER BY id DESC',
            $limit,
            $offset
        );
        $revisionsData = $this->em->getConnection()->fetchAllAssociative($query);

        $revisions = [];
        foreach ($revisionsData as $row) {
            $timestamp = \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']);
            \assert(false !== $timestamp);

            $revisions[] = new Revision($row['id'], $timestamp, $row['username']);
        }

        return $revisions;
    }

    /**
     * NEXT_MAJOR: Remove this method.
     *
     * @return ChangedEntity<object>[]
     *
     * @deprecated this function name is misspelled.
     *             Suggest using findEntitiesChangedAtRevision instead.
     */
    public function findEntitesChangedAtRevision(string|int $revision)
    {
        return $this->findEntitiesChangedAtRevision($revision);
    }

    /**
     * Return a list of ChangedEntity instances created at the given revision.
     *
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws Exception
     * @throws ORMException
     * @throws \RuntimeException
     * @throws DeletedException
     *
     * @return ChangedEntity<object>[]
     */
    public function findEntitiesChangedAtRevision(string|int $revision)
    {
        $auditedEntities = $this->metadataFactory->getAllClassNames();

        $changedEntities = [];
        foreach ($auditedEntities as $className) {
            $classMetadata = $this->em->getClassMetadata($className);

            if ($classMetadata->isInheritanceTypeSingleTable() && \count($classMetadata->subClasses) > 0) {
                continue;
            }

            $tableName = $this->config->getTableName($classMetadata);
            $params = [];

            $whereSQL = 'e.'.$this->config->getRevisionFieldName().' = ?';
            $columnList = 'e.'.$this->config->getRevisionTypeFieldName();
            $params[] = $revision;
            $columnMap = [];

            foreach ($classMetadata->fieldNames as $columnName => $field) {
                $type = Type::getType($classMetadata->fieldMappings[$field]['type']);
                $tableAlias = $classMetadata->isInheritanceTypeJoined()
                    && $classMetadata->isInheritedField($field)
                    && !$classMetadata->isIdentifier($field)
                        ? 're' // root entity
                        : 'e';
                $columnList .= ', '.$type->convertToPHPValueSQL(
                    $tableAlias.'.'.$this->quoteStrategy->getColumnName($field, $classMetadata, $this->platform),
                    $this->platform
                ).' AS '.$this->platform->quoteSingleIdentifier($field);
                $columnMap[$field] = $this->getSQLResultCasing($this->platform, $columnName);
            }

            foreach ($classMetadata->associationMappings as $assoc) {
                if (
                    ($assoc['type'] & ClassMetadata::TO_ONE) > 0
                    && true === $assoc['isOwningSide']
                    && isset($assoc['targetToSourceKeyColumns'])
                ) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columnList .= ', '.$sourceCol;
                        $columnMap[$sourceCol] = $this->getSQLResultCasing($this->platform, $sourceCol);
                    }
                }
            }

            $joinSql = '';
            if (
                $classMetadata->isInheritanceTypeSingleTable()
                && null !== $classMetadata->discriminatorColumn
            ) {
                $columnList .= ', e.'.$classMetadata->discriminatorColumn['name'];
                $whereSQL .= ' AND e.'.$classMetadata->discriminatorColumn['fieldName'].' = ?';
                $params[] = $classMetadata->discriminatorValue;
            } elseif (
                $classMetadata->isInheritanceTypeJoined()
                && $classMetadata->rootEntityName !== $classMetadata->name
                && null !== $classMetadata->discriminatorColumn
            ) {
                $columnList .= ', re.'.$classMetadata->discriminatorColumn['name'];

                $rootClass = $this->em->getClassMetadata($classMetadata->rootEntityName);
                $rootTableName = $this->config->getTableName($rootClass);

                $joinSql = "INNER JOIN {$rootTableName} re ON";
                $joinSql .= ' re.'.$this->config->getRevisionFieldName().' = e.'.$this->config->getRevisionFieldName();
                foreach ($classMetadata->getIdentifierColumnNames() as $name) {
                    $joinSql .= " AND re.$name = e.$name";
                }
            }

            $query = 'SELECT '.$columnList.' FROM '.$tableName.' e '.$joinSql.' WHERE '.$whereSQL;
            $revisionsData = $this->em->getConnection()->fetchAllAssociative($query, $params);

            foreach ($revisionsData as $row) {
                $id = [];

                foreach ($classMetadata->identifier as $idField) {
                    $id[$idField] = $row[$idField];
                }

                $entity = $this->createEntity($className, $columnMap, $row, $revision);
                $changedEntities[] = new ChangedEntity(
                    $className,
                    $id,
                    $row[$this->config->getRevisionTypeFieldName()],
                    $entity
                );
            }
        }

        return $changedEntities;
    }

    /**
     * Return the revision object for a particular revision.
     *
     * @throws Exception
     * @throws InvalidRevisionException
     *
     * @return Revision
     */
    public function findRevision(string|int $revision)
    {
        $query = 'SELECT * FROM '.$this->config->getRevisionTableName().' r WHERE r.id = ?';
        $revisionsData = $this->em->getConnection()->fetchAllAssociative($query, [$revision]);

        if (1 === \count($revisionsData)) {
            $timestamp = \DateTime::createFromFormat(
                $this->platform->getDateTimeFormatString(),
                $revisionsData[0]['timestamp']
            );
            \assert(false !== $timestamp);

            return new Revision($revisionsData[0]['id'], $timestamp, $revisionsData[0]['username']);
        }
        throw new InvalidRevisionException($revision);
    }

    /**
     * Find all revisions that were made of entity class with given id.
     *
     * @param string                               $className
     * @param int|string|array<string, int|string> $id
     *
     * @throws Exception
     * @throws NotAuditedException
     *
     * @return Revision[]
     *
     * @phpstan-param class-string                 $className
     */
    public function findRevisions($className, int|string|array $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $classMetadata = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($classMetadata);

        if (!\is_array($id)) {
            $id = [$classMetadata->identifier[0] => $id];
        }

        $whereSQL = '';
        foreach ($classMetadata->identifier as $idField) {
            if (isset($classMetadata->fieldMappings[$idField])) {
                if ('' !== $whereSQL) {
                    $whereSQL .= ' AND ';
                }
                $whereSQL .= 'e.'.$classMetadata->fieldMappings[$idField]['columnName'].' = ?';
            } elseif (isset($classMetadata->associationMappings[$idField]['joinColumns'])) {
                if ('' !== $whereSQL) {
                    $whereSQL .= ' AND ';
                }
                $whereSQL .= 'e.'.$classMetadata->associationMappings[$idField]['joinColumns'][0]['name'].' = ?';
            }
        }

        $query = sprintf(
            'SELECT r.* FROM %s r INNER JOIN %s e ON r.id = e.%s  WHERE %s ORDER BY r.id DESC',
            $this->config->getRevisionTableName(),
            $tableName,
            $this->config->getRevisionFieldName(),
            $whereSQL
        );
        $revisionsData = $this->em->getConnection()->fetchAllAssociative($query, array_values($id));

        $revisions = [];
        foreach ($revisionsData as $row) {
            $timestamp = \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']);
            \assert(false !== $timestamp);

            $revisions[] = new Revision($row['id'], $timestamp, $row['username']);
        }

        return $revisions;
    }

    /**
     * NEXT_MAJOR: Add NoRevisionFoundException as possible exception.
     * Gets the current revision of the entity with given ID.
     *
     * @param string                               $className
     * @param int|string|array<string, int|string> $id
     *
     * @throws Exception
     * @throws NotAuditedException
     *
     * @phpstan-param class-string                 $className
     */
    public function getCurrentRevision($className, int|string|array $id): int|string|null
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $classMetadata = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($classMetadata);

        if (!\is_array($id)) {
            $id = [$classMetadata->identifier[0] => $id];
        }

        $whereSQL = '';
        foreach ($classMetadata->identifier as $idField) {
            if (isset($classMetadata->fieldMappings[$idField])) {
                if ('' !== $whereSQL) {
                    $whereSQL .= ' AND ';
                }
                $whereSQL .= 'e.'.$classMetadata->fieldMappings[$idField]['columnName'].' = ?';
            } elseif (isset($classMetadata->associationMappings[$idField]['joinColumns'])) {
                if ('' !== $whereSQL) {
                    $whereSQL .= ' AND ';
                }
                $whereSQL .= 'e.'.$classMetadata->associationMappings[$idField]['joinColumns'][0]['name'].' = ?';
            }
        }

        $query = 'SELECT e.'.$this->config->getRevisionFieldName().' FROM '.$tableName.' e '.
            ' WHERE '.$whereSQL.' ORDER BY e.'.$this->config->getRevisionFieldName().' DESC';

        $revision = $this->em->getConnection()->fetchOne($query, array_values($id));

        if (false === $revision) {
            // NEXT_MAJOR: Remove next line and uncomment the following one, also remove "null" as possible return type.
            return null;
            // throw new NoRevisionFoundException($className, $id, null);
        }

        return $revision;
    }

    /**
     * Get an array with the differences of between two specific revisions of
     * an object with a given id.
     *
     * @param string $className
     *
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws Exception
     * @throws ORMException
     * @throws \RuntimeException
     *
     * @return array<string, array<string, mixed>>
     *
     * @phpstan-param class-string $className
     * @phpstan-return array<string, array{old: mixed, new: mixed, same: mixed}>
     */
    public function diff($className, int|string $id, int|string $oldRevision, int|string $newRevision)
    {
        $oldObject = $this->find($className, $id, $oldRevision);
        $newObject = $this->find($className, $id, $newRevision);

        $oldValues = null !== $oldObject ? $this->getEntityValues($className, $oldObject) : [];
        $newValues = null !== $newObject ? $this->getEntityValues($className, $newObject) : [];

        $differ = new ArrayDiff();

        return $differ->diff($oldValues, $newValues);
    }

    /**
     * Get the values for a specific entity as an associative array.
     *
     * @param string $className
     * @param object $entity
     *
     * @return array<string, mixed>
     *
     * @phpstan-param class-string $className
     */
    public function getEntityValues($className, $entity)
    {
        $metadata = $this->em->getClassMetadata($className);
        $fields = $metadata->getFieldNames();

        $return = [];
        foreach ($fields as $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $return;
    }

    /**
     * @template T of object
     *
     * @param string                               $className
     * @param int|string|array<string, int|string> $id
     *
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws Exception
     * @throws ORMException
     * @throws DeletedException
     *
     * @return array<object|null>
     *
     * @phpstan-param class-string<T>              $className
     * @phpstan-return array<T|null>
     */
    public function getEntityHistory($className, int|string|array $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        /** @var ClassMetadata<T> $classMetadata */
        $classMetadata = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($classMetadata);

        if (!\is_array($id)) {
            $id = [$classMetadata->identifier[0] => $id];
        }

        $whereId = [];
        foreach ($classMetadata->identifier as $idField) {
            if (isset($classMetadata->fieldMappings[$idField])) {
                $columnName = $classMetadata->fieldMappings[$idField]['columnName'];
            } elseif (isset($classMetadata->associationMappings[$idField]['joinColumns'])) {
                $columnName = $classMetadata->associationMappings[$idField]['joinColumns'][0]['name'];
            } else {
                continue;
            }

            $whereId[] = "{$columnName} = ?";
        }

        $whereSQL = implode(' AND ', $whereId);
        $columnList = [$this->config->getRevisionFieldName()];
        $columnMap = [];

        foreach ($classMetadata->fieldNames as $columnName => $field) {
            $type = Type::getType($classMetadata->fieldMappings[$field]['type']);
            $columnList[] = $type->convertToPHPValueSQL(
                $this->quoteStrategy->getColumnName($field, $classMetadata, $this->platform),
                $this->platform
            ).' AS '.$this->platform->quoteSingleIdentifier($field);
            $columnMap[$field] = $this->getSQLResultCasing($this->platform, $columnName);
        }

        foreach ($classMetadata->associationMappings as $assoc) {
            if (
                ($assoc['type'] & ClassMetadata::TO_ONE) === 0
                || false === $assoc['isOwningSide']
                || !isset($assoc['targetToSourceKeyColumns'])
            ) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                $columnList[] = $sourceCol;
                $columnMap[$sourceCol] = $this->getSQLResultCasing($this->platform, $sourceCol);
            }
        }

        $values = array_values($id);

        $query = sprintf(
            'SELECT %s FROM %s e WHERE %s ORDER BY e.%s DESC',
            implode(', ', $columnList),
            $tableName,
            $whereSQL,
            $this->config->getRevisionFieldName()
        );

        $stmt = $this->em->getConnection()->executeQuery($query, $values);

        $result = [];
        while ($row = $stmt->fetchAssociative()) {
            $rev = $row[$this->config->getRevisionFieldName()];
            unset($row[$this->config->getRevisionFieldName()]);
            $result[] = $this->createEntity($classMetadata->name, $columnMap, $row, $rev);
        }

        return $result;
    }

    /**
     * @param string $className
     *
     * @return EntityPersister
     *
     * @phpstan-param class-string $className
     */
    protected function getEntityPersister($className)
    {
        $uow = $this->em->getUnitOfWork();

        return $uow->getEntityPersister($className);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * @template T of object
     *
     * @param string                         $className
     * @param array<string, string>          $columnMap
     * @param array<string, int|string|null> $data
     *
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws Exception
     * @throws ORMException
     * @throws \RuntimeException
     *
     * @return object
     *
     * @phpstan-param class-string<T>        $className
     * @phpstan-return T
     */
    private function createEntity($className, array $columnMap, array $data, int|string $revision)
    {
        $classMetadata = $this->em->getClassMetadata($className);

        // lookup revisioned entity cache
        $keyParts = [];

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {
            $keyParts[] = $data[$name];
        }

        $key = implode(':', $keyParts);

        if (isset($this->entityCache[$className][$key][$revision])) {
            /** @phpstan-var T $cachedEntity */
            $cachedEntity = $this->entityCache[$className][$key][$revision];

            return $cachedEntity;
        }

        if (
            !$classMetadata->isInheritanceTypeNone()
            && null !== $classMetadata->discriminatorColumn
        ) {
            if (!isset($data[$classMetadata->discriminatorColumn['name']])) {
                throw new \RuntimeException('Expecting discriminator value in data set.');
            }
            $discriminator = $data[$classMetadata->discriminatorColumn['name']];
            if (!isset($classMetadata->discriminatorMap[$discriminator])) {
                throw new \RuntimeException("No mapping found for [{$discriminator}].");
            }

            if (isset($classMetadata->discriminatorValue)) {
                /** @phpstan-var T $entity */
                $entity = $this->em->getClassMetadata($classMetadata->discriminatorMap[$discriminator])->newInstance();
            } else {
                // a complex case when ToOne binding is against AbstractEntity having no discriminator
                $pk = [];

                foreach ($classMetadata->identifier as $field) {
                    if (isset($data[$field])) {
                        $pk[$classMetadata->getColumnName($field)] = $data[$field];
                    }
                }

                /** @phpstan-var class-string<T> $classNameDiscriminator */
                $classNameDiscriminator = $classMetadata->discriminatorMap[$discriminator];

                /** @phpstan-var T $entity */
                $entity = $this->find($classNameDiscriminator, $pk, $revision);

                return $entity;
            }
        } else {
            /** @phpstan-var T $entity */
            $entity = $classMetadata->newInstance();
        }

        // cache the entity to prevent circular references
        $this->entityCache[$className][$key][$revision] = $entity;

        foreach ($data as $field => $value) {
            if (isset($classMetadata->fieldMappings[$field])) {
                $type = Type::getType($classMetadata->fieldMappings[$field]['type']);
                $value = $type->convertToPHPValue($value, $this->platform);

                $reflField = $classMetadata->reflFields[$field];
                \assert(null !== $reflField);
                $reflField->setValue($entity, $value);
            }
        }

        foreach ($classMetadata->associationMappings as $field => $assoc) {
            /** @phpstan-var class-string<T> $targetEntity */
            $targetEntity = $assoc['targetEntity'];
            $targetClass = $this->em->getClassMetadata($targetEntity);

            $mappedBy = $assoc['mappedBy'] ?? null;

            if (0 !== ($assoc['type'] & ClassMetadata::TO_ONE)) {
                if ($this->metadataFactory->isAudited($targetEntity)) {
                    if ($this->loadAuditedEntities) {
                        // Primary Key. Used for audit tables queries.
                        $pk = [];
                        // Primary Field. Used when fallback to Doctrine finder.
                        $pf = [];

                        if (true === $assoc['isOwningSide'] && isset($assoc['targetToSourceKeyColumns'])) {
                            foreach ($assoc['targetToSourceKeyColumns'] as $foreign => $local) {
                                $key = $data[$columnMap[$local]];
                                if (null === $key) {
                                    continue;
                                }

                                $pk[$foreign] = $key;
                                $pf[$foreign] = $key;
                            }
                        } elseif (null !== $mappedBy) {
                            $otherEntityAssoc = $this->em->getClassMetadata($targetEntity)
                                ->associationMappings[$mappedBy];

                            if (isset($otherEntityAssoc['targetToSourceKeyColumns'])) {
                                foreach ($otherEntityAssoc['targetToSourceKeyColumns'] as $local => $foreign) {
                                    $key = $data[$classMetadata->getFieldName($local)];
                                    if (null === $key) {
                                        continue;
                                    }

                                    $pk[$foreign] = $key;
                                    $pf[$otherEntityAssoc['fieldName']] = $key;
                                }
                            }
                        }

                        if ([] === $pk) {
                            $value = null;
                        } else {
                            try {
                                $value = $this->find(
                                    $targetClass->name,
                                    $pk,
                                    $revision,
                                    ['threatDeletionsAsExceptions' => true]
                                );
                            } catch (DeletedException) {
                                $value = null;
                            } catch (NoRevisionFoundException) {
                                // The entity does not have any revision yet. So let's get the actual state of it.
                                $value = $this->em->getRepository($targetClass->name)->findOneBy($pf);
                            }
                        }
                    } else {
                        $value = null;
                    }
                } else {
                    if ($this->loadNativeEntities) {
                        if (true === $assoc['isOwningSide'] && isset($assoc['targetToSourceKeyColumns'])) {
                            $associatedId = [];
                            foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                                $joinColumnValue = $data[$columnMap[$srcColumn]] ?? null;
                                if (null !== $joinColumnValue) {
                                    $targetField = $targetClass->fieldNames[$targetColumn];
                                    $joinColumnType = Type::getType($targetClass->fieldMappings[$targetField]['type']);
                                    $joinColumnValue = $joinColumnType->convertToPHPValue(
                                        $joinColumnValue,
                                        $this->platform
                                    );
                                    $associatedId[$targetField] = $joinColumnValue;
                                }
                            }
                            if ([] === $associatedId) {
                                // Foreign key is NULL
                                $value = null;
                            } else {
                                $value = $this->em->getReference($targetClass->name, $associatedId);
                            }
                        } else {
                            // Inverse side of x-to-one can never be lazy
                            $value = $this->getEntityPersister($targetEntity)
                                ->loadOneToOneEntity($assoc, $entity);
                        }
                    } else {
                        $value = null;
                    }
                }

                $reflField = $classMetadata->reflFields[$field];
                \assert(null !== $reflField);
                $reflField->setValue($entity, $value);
            } elseif (
                0 !== ($assoc['type'] & ClassMetadata::ONE_TO_MANY)
                && null !== $mappedBy
                && isset($targetClass->associationMappings[$mappedBy]['sourceToTargetKeyColumns'])
            ) {
                if ($this->metadataFactory->isAudited($targetEntity)) {
                    if ($this->loadAuditedCollections) {
                        $foreignKeys = [];
                        foreach ($targetClass->associationMappings[$mappedBy]['sourceToTargetKeyColumns'] as $local => $foreign) {
                            $field = $classMetadata->getFieldForColumn($foreign);
                            $reflField = $classMetadata->reflFields[$field];
                            \assert(null !== $reflField);
                            $foreignKeys[$local] = $reflField->getValue($entity);
                        }

                        $collection = new AuditedCollection(
                            $this,
                            $targetClass->name,
                            $targetClass,
                            $assoc,
                            $foreignKeys,
                            $revision
                        );
                    } else {
                        $collection = new ArrayCollection();
                    }
                } else {
                    if ($this->loadNativeCollections) {
                        $collection = new PersistentCollection($this->em, $targetClass, new ArrayCollection());

                        $this->getEntityPersister($targetEntity)
                            ->loadOneToManyCollection($assoc, $entity, $collection);
                    } else {
                        $collection = new ArrayCollection();
                    }
                }

                $reflField = $classMetadata->reflFields[$assoc['fieldName']];
                \assert(null !== $reflField);
                $reflField->setValue($entity, $collection);
            } elseif (0 !== ($assoc['type'] & ClassMetadata::MANY_TO_MANY)) {
                if ($assoc['isOwningSide'] && isset(
                    $assoc['relationToSourceKeyColumns'],
                    $assoc['relationToTargetKeyColumns'],
                    $assoc['joinTable']['name']
                )) {
                    $whereId = [$this->config->getRevisionFieldName().' = ?'];
                    $values = [$revision];
                    foreach ($assoc['relationToSourceKeyColumns'] as $sourceKeyJoinColumn => $sourceKeyColumn) {
                        $whereId[] = "{$sourceKeyJoinColumn} = ?";

                        $reflField = $classMetadata->reflFields['id'];
                        \assert(null !== $reflField);

                        $values[] = $reflField->getValue($entity);
                    }

                    $whereSQL = implode(' AND ', $whereId);
                    $columnList = [
                        $this->config->getRevisionFieldName(),
                        $this->config->getRevisionTypeFieldName(),
                    ];
                    $tableName = $this->config->getTablePrefix()
                        .$assoc['joinTable']['name']
                        .$this->config->getTableSuffix();

                    foreach ($assoc['relationToTargetKeyColumns'] as $targetKeyJoinColumn => $targetKeyColumn) {
                        $columnList[] = $targetKeyJoinColumn;
                    }

                    $query = sprintf(
                        'SELECT %s FROM %s e WHERE %s ORDER BY e.%s DESC',
                        implode(', ', $columnList),
                        $tableName,
                        $whereSQL,
                        $this->config->getRevisionFieldName()
                    );

                    $rows = $this->em->getConnection()->fetchAllAssociative($query, $values);

                    /** @var ArrayCollection<int, object> */
                    $collection = new ArrayCollection();
                    if (0 < \count($rows)) {
                        if ($this->metadataFactory->isAudited($targetEntity)) {
                            foreach ($rows as $row) {
                                $id = [];

                                /** @phpstan-var string $targetKeyColumn */
                                foreach ($assoc['relationToTargetKeyColumns'] as $targetKeyJoinColumn => $targetKeyColumn) {
                                    $joinKey = $row[$targetKeyJoinColumn];
                                    $id[$targetKeyColumn] = $joinKey;
                                }
                                $object = $this->find($targetClass->getName(), $id, $revision);
                                if (null !== $object) {
                                    $collection->add($object);
                                }
                            }
                        } else {
                            $reflField = $classMetadata->reflFields[$assoc['fieldName']];
                            \assert(null !== $reflField);

                            if ($this->loadNativeCollections) {
                                $collection = new PersistentCollection(
                                    $this->em,
                                    $targetClass,
                                    new ArrayCollection()
                                );

                                $this->getEntityPersister($targetEntity)
                                    ->loadManyToManyCollection($assoc, $entity, $collection);

                                $reflField->setValue($entity, $collection);
                            } else {
                                $reflField->setValue($entity, new ArrayCollection());
                            }
                        }
                    }
                    $reflField = $classMetadata->reflFields[$field];
                    \assert(null !== $reflField);

                    $reflField->setValue($entity, $collection);
                } elseif (isset($targetClass->associationMappings[$mappedBy])) {
                    $targetAssoc = $targetClass->associationMappings[$mappedBy];
                    $whereId = [$this->config->getRevisionFieldName().' = ?'];
                    $values = [$revision];

                    /** @var ArrayCollection<int, object> */
                    $collection = new ArrayCollection();

                    // if the  owning side of the relation is audited, fetch the audited values, otherwise fetch
                    // data from the main table
                    if ($this->metadataFactory->isAudited($assoc['targetEntity'])
                        && isset(
                            $targetAssoc['relationToSourceKeyColumns'],
                            $targetAssoc['relationToSourceKeyColumns'],
                            $targetAssoc['joinTable']['name'],
                            $targetAssoc['relationToTargetKeyColumns']
                        )) {
                        foreach ($targetAssoc['relationToTargetKeyColumns'] as $targetKeyJoinColumn => $targetKeyColumn) {
                            $whereId[] = "{$targetKeyJoinColumn} = ?";
                            $reflField = $classMetadata->reflFields['id'];
                            \assert(null !== $reflField);
                            $values[] = $reflField->getValue($entity);
                        }

                        $whereSQL = implode(' AND ', $whereId);
                        $columnList = [
                            $this->config->getRevisionFieldName(),
                            $this->config->getRevisionTypeFieldName(),
                        ];

                        $tableName = $this->config->getTablePrefix()
                            .$targetAssoc['joinTable']['name']
                            .$this->config->getTableSuffix();

                        foreach ($targetAssoc['relationToSourceKeyColumns'] as $sourceKeyJoinColumn => $sourceKeyColumn) {
                            $columnList[] = $sourceKeyJoinColumn;
                        }
                        $query = sprintf(
                            'SELECT %s FROM %s e WHERE %s ORDER BY e.%s DESC',
                            implode(', ', $columnList),
                            $tableName,
                            $whereSQL,
                            $this->config->getRevisionFieldName()
                        );

                        $rows = $this->em->getConnection()->fetchAllAssociative($query, $values);

                        if (0 < \count($rows)) {
                            foreach ($rows as $row) {
                                $id = [];
                                /** @phpstan-var string $sourceKeyColumn */
                                foreach ($targetAssoc['relationToSourceKeyColumns'] as $sourceKeyJoinColumn => $sourceKeyColumn) {
                                    $joinKey = $row[$sourceKeyJoinColumn];
                                    $id[$sourceKeyColumn] = $joinKey;
                                }

                                $object = $this->find($targetClass->getName(), $id, $revision);
                                if (null !== $object) {
                                    $collection->add($object);
                                }
                            }
                        }
                    } else {
                        $reflField = $classMetadata->reflFields[$assoc['fieldName']];
                        \assert(null !== $reflField);

                        if ($this->loadNativeCollections) {
                            $collection = new PersistentCollection(
                                $this->em,
                                $targetClass,
                                new ArrayCollection()
                            );

                            $this->getEntityPersister($assoc['targetEntity'])
                                ->loadManyToManyCollection($assoc, $entity, $collection);

                            $reflField->setValue($entity, $collection);
                        } else {
                            $reflField->setValue($entity, new ArrayCollection());
                        }
                    }
                    $reflField = $classMetadata->reflFields[$field];
                    \assert(null !== $reflField);
                    $reflField->setValue($entity, $collection);
                }
            } else {
                // Inject collection
                $reflField = $classMetadata->reflFields[$field];
                \assert(null !== $reflField);
                $reflField->setValue($entity, new ArrayCollection());
            }
        }

        return $entity;
    }
}
