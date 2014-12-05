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

namespace SimpleThings\EntityAudit;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use SimpleThings\EntityAudit\Exception\DeletedException;
use SimpleThings\EntityAudit\Exception\InvalidRevisionException;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\ArrayDiff;

class AuditReader
{
    private $em;

    private $config;

    private $metadataFactory;

    /**
     * Entity cache to prevent circular references
     * @var array
     */
    private $entityCache;

    /**
     * @param EntityManager $em
     * @param AuditConfiguration $config
     * @param MetadataFactory $factory
     */
    public function __construct(EntityManager $em, AuditConfiguration $config, MetadataFactory $factory)
    {
        $this->em = $em;
        $this->config = $config;
        $this->metadataFactory = $factory;
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
    }

    /**
     * Clears entity cache. Call this if you are fetching subsequent revisions using same AuditManager.
     */
    public function clearEntityCache()
    {
        $this->entityCache = array();
    }

    /**
     * Find a class at the specific revision.
     *
     * This method does not require the revision to be exact but it also searches for an earlier revision
     * of this entity and always returns the latest revision below or equal the given revision. Commonly, it
     * returns last revision INCLUDING "DEL" revision. If you want to throw exception instead, set
     * $threatDeletionAsException to true.
     *
     * @param string $className
     * @param mixed $id
     * @param int $revision
     * @param bool $threatDeletionAsException
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     * @return object
     */
    public function find($className, $id, $revision, $threatDeletionAsException = false)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL  = "e." . $this->config->getRevisionFieldName() ." <= ?";

        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } else if (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            }

            $whereSQL .= " AND e." . $columnName . " = ?";
        }

        $columnList = array('e.'.$this->config->getRevisionTypeFieldName());
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            $tableAlias = $class->isInheritanceTypeJoined() && $class->isInheritedField($field) && !$class->isIdentifier($field)
                ? 're' // root entity
                : 'e';

            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList[] = $tableAlias.'.'.$type->convertToPHPValueSQL(
                $class->getQuotedColumnName($field, $this->platform), $this->platform) .
                ' AS ' . $this->em->getConnection()->quote($field);
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) == 0 || !$assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                $tableAlias = $class->isInheritanceTypeJoined() && $class->isInheritedField($field) && !$class->isIdentifier($field)
                    ? 're' // root entity
                    : 'e';
                $columnList[] = $tableAlias.'.'.$sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $joinSql = '';
        if ($class->isInheritanceTypeJoined()
            && !$class->isRootEntity()) {
            $rootClass = $this->em->getClassMetadata($class->rootEntityName);
            $rootTableName = $this->config->getTablePrefix() . $rootClass->table['name'] . $this->config->getTableSuffix();
            $joinSql = "INNER JOIN {$rootTableName} re ON re.id = e.id AND re.rev = e.rev";
        }

        $values = array_merge(array($revision), array_values($id));

        if (!$class->isInheritanceTypeNone()) {
            $columnList[] = $class->discriminatorColumn['name'];
            if ($class->isInheritanceTypeSingleTable()
                && $class->discriminatorValue !== null) {

                $whereSQL .= " AND " . $class->discriminatorColumn['name'] . " = ?";
                $values[] = $class->discriminatorValue;
            }
        }

        $query = "SELECT " . implode(', ', $columnList) . " FROM " . $tableName . " e " . $joinSql . " WHERE " . $whereSQL . " ORDER BY e.rev DESC";

        $row = $this->em->getConnection()->fetchAssoc($query, $values);

        if (!$row) {
            throw new NoRevisionFoundException($class->name, $id, $revision);
        }

        if ($threatDeletionAsException && $row[$this->config->getRevisionTypeFieldName()] == 'DEL') {
            throw new DeletedException($class->name, $id, $revision);
        }

        unset($row[$this->config->getRevisionTypeFieldName()]);

        return $this->createEntity($class->name, $row, $revision);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * @param string $className
     * @param array $data
     * @param $revision
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     * @return object
     */
    private function createEntity($className, array $data, $revision)
    {
        $class = $this->em->getClassMetadata($className);

        //lookup revisioned entity cache
        $keyParts = array();

        foreach($class->getIdentifierFieldNames() as $name) {
            $keyParts[] = $data[$name];
        }

        $key = implode(':', $keyParts);

        if (isset($this->entityCache[$className]) &&
            isset($this->entityCache[$className][$key]) &&
            isset($this->entityCache[$className][$key][$revision])
        ) {
            return $this->entityCache[$className][$key][$revision];
        }

        if (!$class->isInheritanceTypeNone()) {
            if (!isset($data[$class->discriminatorColumn['name']])) {
                throw new \RuntimeException('Expecting discriminator value in data set.');
            }
            $discriminator = $data[$class->discriminatorColumn['name']];
            if (!isset($class->discriminatorMap[$discriminator])) {
                throw new \RuntimeException("No mapping found for [{$discriminator}].");
            }
            $entity = $this->em->getClassMetadata($class->discriminatorMap[$discriminator])
                ->newInstance();
        } else {
            $entity = $class->newInstance();
        }

        //cache the entity to prevent circular references
        $this->entityCache[$className][$key][$revision] = $entity;

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $value = $type->convertToPHPValue($value, $this->platform);
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetched'][$className][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                if ($this->metadataFactory->isAudited($assoc['targetEntity'])) {
                    //there is no difference for lazy loading, it won't work
                    $pk = array();
                    foreach ($assoc['targetToSourceKeyColumns'] as $foreign => $local) {
                        $pk[$foreign] = $data[$local];
                    }

                    $pk = array_filter($pk, function($value) {
                        return !is_null($value);
                    });

                    if (!$pk) {
                        $class->reflFields[$field]->setValue($entity, null);
                    } else {
                        try {
                            $value = $this->find($targetClass->name, $pk, $revision, true);
                        } catch (DeletedException $e) {
                            $value = null;
                        }

                        $class->reflFields[$field]->setValue($entity, $value);
                    }
                } else {
                    if ($assoc['isOwningSide']) {
                        $associatedId = array();
                        foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                            $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;
                            if ($joinColumnValue !== null) {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        }
                        if (!$associatedId) {
                            // Foreign key is NULL
                            $class->reflFields[$field]->setValue($entity, null);
                        } else {
                            $associatedEntity = $this->em->getReference($targetClass->name, $associatedId);
                            $class->reflFields[$field]->setValue($entity, $associatedEntity);
                        }
                    } else {
                        // Inverse side of x-to-one can never be lazy
                        $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToOneEntity($assoc, $entity));
                    }
                }
            } elseif ($assoc['type'] & ClassMetadata::ONE_TO_MANY) {
                if ($this->metadataFactory->isAudited($assoc['targetEntity'])) {
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
                    }

                    $class->reflFields[$assoc['fieldName']]->setValue($entity, new ArrayCollection($entities));
                } else {
                    $class->reflFields[$assoc['fieldName']]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])
                        ->loadOneToManyCollection($assoc, $entity, new PersistentCollection($this->em, $targetClass, new ArrayCollection())));
                }
            } else {
                throw new \Exception(sprintf('Association type %d is not yet supported', $assoc['type']));
            }
        }

        return $entity;
    }

    /**
     * Return a list of all revisions.
     *
     * @param int $limit
     * @param int $offset
     * @return Revision[]
     */
    public function findRevisionHistory($limit = 20, $offset = 0)
    {
        $this->platform = $this->em->getConnection()->getDatabasePlatform();

        $query = $this->platform->modifyLimitQuery(
            "SELECT * FROM " . $this->config->getRevisionTableName() . " ORDER BY id DESC", $limit, $offset
        );
        $revisionsData = $this->em->getConnection()->fetchAll($query);

        $revisions = array();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username']
            );
        }
        return $revisions;
    }

    /**
     * @deprecated this function name is misspelled.
     * Suggest using findEntitiesChangedAtRevision instead.
     */
    public function findEntitesChangedAtRevision($revision)
    {
        return $this->findEntitiesChangedAtRevision($revision);
    }

    /**
     * Return a list of ChangedEntity instances created at the given revision.
     *
     * @param int $revision
     * @return ChangedEntity[]
     */
    public function findEntitiesChangedAtRevision($revision)
    {
        $auditedEntities = $this->metadataFactory->getAllClassNames();

        $changedEntities = array();
        foreach ($auditedEntities AS $className) {
            $class = $this->em->getClassMetadata($className);

            if ($class->isInheritanceTypeSingleTable() && count($class->subClasses) > 0) {
                continue;
            }

            $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
            $params = array();

            $whereSQL   = "e." . $this->config->getRevisionFieldName() ." = ?";
            $columnList = "e." . $this->config->getRevisionTypeFieldName();
            $params[] = $revision;
            $columnMap  = array();

            foreach ($class->fieldNames as $columnName => $field) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $tableAlias = $class->isInheritanceTypeJoined() && $class->isInheritedField($field)	&& ! $class->isIdentifier($field)
                    ? 're' // root entity
                    : 'e';
                $columnList .= ', ' . $tableAlias.'.'.$type->convertToPHPValueSQL(
                    $class->getQuotedColumnName($field, $this->platform), $this->platform) . ' AS ' . $field;
                $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
            }

            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columnList .= ', ' . $sourceCol;
                        $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
                    }
                }
            }

            $joinSql = '';
            if ($class->isInheritanceTypeSingleTable()) {
                $columnList .= ', e.' . $class->discriminatorColumn['name'];
                $whereSQL .= " AND e." . $class->discriminatorColumn['fieldName'] . " = ?";
                $params[] = $class->discriminatorValue;
            } elseif ($class->isInheritanceTypeJoined() && ! $class->isRootEntity()) {
                $columnList .= ', re.' . $class->discriminatorColumn['name'];
                $rootClass = $this->em->getClassMetadata($class->rootEntityName);
                $rootTableName = $this->config->getTablePrefix() . $rootClass->table['name'] . $this->config->getTableSuffix();
                $joinSql = "INNER JOIN {$rootTableName} re ON re.id = e.id AND re.rev = e.rev";
            }

            $this->platform = $this->em->getConnection()->getDatabasePlatform();
            $query = "SELECT " . $columnList . " FROM " . $tableName . " e " . $joinSql . " WHERE " . $whereSQL;
            $revisionsData = $this->em->getConnection()->executeQuery($query, $params);

            foreach ($revisionsData AS $row) {
                $id   = array();
                $data = array();

                foreach ($class->identifier AS $idField) {
                    $id[$idField] = $row[$idField];
                }

                $entity = $this->createEntity($className, $row, $revision);
                $changedEntities[] = new ChangedEntity($className, $id, $row[$this->config->getRevisionTypeFieldName()], $entity);
            }
        }
        return $changedEntities;
    }

    /**
     * Return the revision object for a particular revision.
     *
     * @param  int $rev
     * @throws InvalidRevisionException
     * @return Revision
     */
    public function findRevision($rev)
    {
        $query = "SELECT * FROM " . $this->config->getRevisionTableName() . " r WHERE r.id = ?";
        $revisionsData = $this->em->getConnection()->fetchAll($query, array($rev));

        if (count($revisionsData) == 1) {
            return new Revision(
                $revisionsData[0]['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $revisionsData[0]['timestamp']),
                $revisionsData[0]['username']
            );
        } else {
            throw new InvalidRevisionException($rev);
        }
    }

    /**
     * Find all revisions that were made of entity class with given id.
     *
     * @param string $className
     * @param mixed $id
     * @throws NotAuditedException
     * @return Revision[]
     */
    public function findRevisions($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL = "";
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->fieldMappings[$idField]['columnName'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }

        $query = "SELECT r.* FROM " . $this->config->getRevisionTableName() . " r " .
                 "INNER JOIN " . $tableName . " e ON r.id = e." . $this->config->getRevisionFieldName() . " WHERE " . $whereSQL . " ORDER BY r.id DESC";
        $revisionsData = $this->em->getConnection()->fetchAll($query, array_values($id));

        $revisions = array();
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username']
            );
        }

        return $revisions;
    }

    /**
     * Gets the current revision of the entity with given ID.
     *
     * @param string $className
     * @param mixed $id
     * @throws NotAuditedException
     * @return integer
     */
    public function getCurrentRevision($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL = "";
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->fieldMappings[$idField]['columnName'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }

        $query = "SELECT e.".$this->config->getRevisionFieldName()." FROM " . $tableName . " e " .
                        " WHERE " . $whereSQL . " ORDER BY e.".$this->config->getRevisionFieldName()." DESC";
        $revision = $this->em->getConnection()->fetchColumn($query, array_values($id));

        return $revision;
    }

    protected function getEntityPersister($entity)
    {
        $uow = $this->em->getUnitOfWork();
        return $uow->getEntityPersister($entity);
    }

    /**
     * Get an array with the differences of between two specific revisions of
     * an object with a given id.
     *
     * @param string $className
     * @param int $id
     * @param int $oldRevision
     * @param int $newRevision
     * @return array
     */
    public function diff($className, $id, $oldRevision, $newRevision)
    {
        $oldObject = $this->find($className, $id, $oldRevision);
        $newObject = $this->find($className, $id, $newRevision);
        
        $oldValues = $this->getEntityValues($className, $oldObject);
        $newValues = $this->getEntityValues($className, $newObject);

        $differ = new ArrayDiff();
        return $differ->diff($oldValues, $newValues);
    }

    /**
     * Get the values for a specific entity as an associative array
     *
     * @param string $className
     * @param object $entity
     * @return array
     */
    public function getEntityValues($className, $entity)
    {
        $metadata = $this->em->getClassMetadata($className);
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $return;
    }

    public function getEntityHistory($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereId = array();
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } else if (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            } else {
                continue;
            }

            $whereId[] = "{$columnName} = ?";
        }

        $whereSQL  = implode(' AND ', $whereId);
        $columnList = array($this->config->getRevisionFieldName());
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList[] = $type->convertToPHPValueSQL(
                               $class->getQuotedColumnName($field, $this->platform), $this->platform) .' AS ' . $field;
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) == 0 || !$assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                $columnList[] = $sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $values = array_values($id);

        $query = "SELECT " . implode(', ', $columnList) . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.rev DESC";
        $stmt = $this->em->getConnection()->executeQuery($query, $values);

        $result = array();
        while ($row = $stmt->fetch(Query::HYDRATE_ARRAY)) {
            $rev = $row[$this->config->getRevisionFieldName()];
            unset($row[$this->config->getRevisionFieldName()]);
            $result[] = $this->createEntity($class->name, $row, $rev);
        }

        return $result;
    }
}
