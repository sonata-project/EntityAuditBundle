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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query;
use SimpleThings\EntityAudit\Collection\AuditedCollection;
use SimpleThings\EntityAudit\Exception\DeletedException;
use SimpleThings\EntityAudit\Exception\InvalidRevisionException;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\ArrayDiff;

class AuditReader
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AuditConfiguration
     */
    private $config;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var QuoteStrategy
     */
    private $quoteStrategy;

    /**
     * Entity cache to prevent circular references
     * @var EntityCache
     */
    private $entityCache;

    /**
     * Decides if audited ToMany collections are loaded
     * @var bool
     */
    private $loadAuditedCollections = true;

    /**
     * Decides if audited ToOne collections are loaded
     * @var bool
     */
    private $loadAuditedEntities = true;

    /**
     * Decides if native (not audited) ToMany collections are loaded
     * @var bool
     */
    private $loadNativeCollections = true;

    /**
     * Decides if native (not audited) ToOne collections are loaded
     * @var bool
     */
    private $loadNativeEntities = true;

    /**
     * @return boolean
     */
    public function isLoadAuditedCollections()
    {
        return $this->loadAuditedCollections;
    }

    /**
     * @param boolean $loadAuditedCollections
     */
    public function setLoadAuditedCollections($loadAuditedCollections)
    {
        $this->loadAuditedCollections = $loadAuditedCollections;
    }

    /**
     * @return boolean
     */
    public function isLoadAuditedEntities()
    {
        return $this->loadAuditedEntities;
    }

    /**
     * @param boolean $loadAuditedEntities
     */
    public function setLoadAuditedEntities($loadAuditedEntities)
    {
        $this->loadAuditedEntities = $loadAuditedEntities;
    }

    /**
     * @return boolean
     */
    public function isLoadNativeCollections()
    {
        return $this->loadNativeCollections;
    }

    /**
     * @param boolean $loadNativeCollections
     */
    public function setLoadNativeCollections($loadNativeCollections)
    {
        $this->loadNativeCollections = $loadNativeCollections;
    }

    /**
     * @return boolean
     */
    public function isLoadNativeEntities()
    {
        return $this->loadNativeEntities;
    }

    /**
     * @param boolean $loadNativeEntities
     */
    public function setLoadNativeEntities($loadNativeEntities)
    {
        $this->loadNativeEntities = $loadNativeEntities;
    }

    /**
     * @param EntityManagerInterface $em
     * @param AuditConfiguration $config
     * @param MetadataFactory $factory
     */
    public function __construct(EntityManagerInterface $em, AuditConfiguration $config, MetadataFactory $factory, EntityCache $entityCache)
    {
        $this->em = $em;
        $this->config = $config;
        $this->metadataFactory = $factory;
        $this->entityCache = $entityCache;
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
        $this->quoteStrategy = $this->em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * @return \Doctrine\DBAL\Connection
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
    public function clearEntityCache()
    {
        $this->entityCache->clear();
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
     * @param array $options
     * @return object
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function find($className, $id, $revision, array $options = array())
    {
        $options = array_merge(array('threatDeletionsAsExceptions' => false), $options);

        if (!$this->metadataFactory->isAudited($className)) {
            throw new NotAuditedException($className);
        }

        /** @var ClassMetadataInfo|ClassMetadata $class */
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($class);

        $whereSQL  = "e." . $this->config->getRevisionFieldName() ." <= ?";

        foreach ($class->identifier AS $idField) {
            if (is_array($id) && count($id) > 0) {
                $idKeys = array_keys($id);
                $columnName = $idKeys[0];
            } else if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } elseif (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            } else {
                throw new \RuntimeException('column name not found  for' . $idField);
            }

            $whereSQL .= " AND e." . $columnName . " = ?";
        }

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $columnList = array('e.'.$this->config->getRevisionTypeFieldName());
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            $tableAlias = $class->isInheritanceTypeJoined() && $class->isInheritedField($field) && !$class->isIdentifier($field)
                ? 're' // root entity
                : 'e';

            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList[] = sprintf(
                '%s AS %s',
                $type->convertToPHPValueSQL(
                    $tableAlias . '.' . $this->quoteStrategy->getColumnName($field, $class, $this->platform),
                    $this->platform
                ),
                $this->platform->quoteSingleIdentifier($field)
            );
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if (($assoc['type'] & ClassMetadata::TO_ONE) == 0 || ! $assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['joinColumnFieldNames'] as $sourceCol) {
                $tableAlias = $class->isInheritanceTypeJoined() &&
                    $class->isInheritedAssociation($assoc['fieldName']) &&
                    !$class->isIdentifier($assoc['fieldName'])
                    ? 're' // root entity
                    : 'e';
                $columnList[] = $tableAlias.'.'.$sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $joinSql = '';
        if ($class->isInheritanceTypeJoined() && $class->name != $class->rootEntityName) {
            /** @var ClassMetadataInfo|ClassMetadata $rootClass */
            $rootClass = $this->em->getClassMetadata($class->rootEntityName);
            $rootTableName = $this->config->getTableName($rootClass);
            $joinSql = "INNER JOIN {$rootTableName} re ON";
            $joinSql .= " re.".$this->config->getRevisionFieldName()." = e.".$this->config->getRevisionFieldName();
            foreach ($class->getIdentifierColumnNames() as $name) {
                $joinSql .= " AND re.$name = e.$name";
            }
        }

        $values = array_merge(array($revision), array_values($id));

        if (!$class->isInheritanceTypeNone()) {
            $columnList[] = $class->discriminatorColumn['name'];
            if ($class->isInheritanceTypeSingleTable()
                && $class->discriminatorValue !== null) {

                // Support for single table inheritance sub-classes
                $allDiscrValues = array_flip($class->discriminatorMap);
                $queriedDiscrValues = array($this->em->getConnection()->quote($class->discriminatorValue));
                foreach ($class->subClasses as $subclassName) {
                    $queriedDiscrValues[] = $this->em->getConnection()->quote($allDiscrValues[$subclassName]);
                }

                $whereSQL .= " AND " . $class->discriminatorColumn['name'] . " IN " . '(' . implode(', ', $queriedDiscrValues) . ')';
            }
        }

        $query = "SELECT " . implode(', ', $columnList) . " FROM " . $tableName . " e " . $joinSql . " WHERE " . $whereSQL . " ORDER BY e.".$this->config->getRevisionFieldName()." DESC";

        $row = $this->em->getConnection()->fetchAssoc($query, $values);

        if (!$row) {
            throw new NoRevisionFoundException($class->name, $id, $revision);
        }

        if ($options['threatDeletionsAsExceptions'] && $row[$this->config->getRevisionTypeFieldName()] == 'DEL') {
            throw new DeletedException($class->name, $id, $revision);
        }

        unset($row[$this->config->getRevisionTypeFieldName()]);

        return $this->createEntity($class->name, $columnMap, $row, $revision);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * @param string $className
     * @param array $columnMap
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
    private function createEntity($className, array $columnMap, array $data, $revision)
    {
        /** @var ClassMetadataInfo|ClassMetadata $class */
        $class = $this->em->getClassMetadata($className);

        //lookup revisioned entity cache
        $keyParts = array();

        foreach($class->getIdentifierFieldNames() as $name) {
            $keyParts[] = $data[$name];
        }

        $key = implode(':', $keyParts);

        if ($this->entityCache->hasEntity($className, $key, $revision)) {
            return $this->entityCache->getEntity($className, $key, $revision);
        }

        if (!$class->isInheritanceTypeNone()) {
            if (!isset($data[$class->discriminatorColumn['name']])) {
                throw new \RuntimeException('Expecting discriminator value in data set.');
            }
            $discriminator = $data[$class->discriminatorColumn['name']];
            if (!isset($class->discriminatorMap[$discriminator])) {
                throw new \RuntimeException("No mapping found for [{$discriminator}].");
            }

            if ($class->discriminatorValue) {
                $entity = $this->em->getClassMetadata($class->discriminatorMap[$discriminator])->newInstance();
            } else {
                //a complex case when ToOne binding is against AbstractEntity having no discriminator
                $pk = array();

                foreach ($class->identifier as $field) {
                    $pk[$class->getColumnName($field)] = $data[$field];
                }

                return $this->find($class->discriminatorMap[$discriminator], $pk, $revision);
            }
        } else {
            $entity = $class->newInstance();
        }

        //cache the entity to prevent circular references
        $this->entityCache->addEntity($className, $key, $revision, $entity);

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

            /** @var ClassMetadataInfo|ClassMetadata $targetClass */
            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                //print_r($targetClass->discriminatorMap);
                if ($this->metadataFactory->isAudited($assoc['targetEntity'])) {
                    if ($this->loadAuditedEntities) {
                        // Primary Key. Used for audit tables queries.
                        $pk = array();
                        // Primary Field. Used when fallback to Doctrine finder.
                        $pf = array();

                        if ($assoc['isOwningSide']) {
                            foreach ($assoc['targetToSourceKeyColumns'] as $foreign => $local) {
                                $pk[$foreign] = $pf[$foreign] = $data[$columnMap[$local]];
                            }
                        } else {
                            /** @var ClassMetadataInfo|ClassMetadata $otherEntityMeta */
                            $otherEntityAssoc = $this->em->getClassMetadata($assoc['targetEntity'])->associationMappings[$assoc['mappedBy']];

                            foreach ($otherEntityAssoc['targetToSourceKeyColumns'] as $local => $foreign) {
                                $pk[$foreign] = $pf[$otherEntityAssoc['fieldName']] = $data[$class->getFieldName($local)];
                            }
                        }

                        $pk = array_filter($pk, function ($value) {
                            return !is_null($value);
                        });

                        if (!$pk) {
                            $class->reflFields[$field]->setValue($entity, null);
                        } else {
                            try {
                                $value = $this->find($targetClass->name, $pk, $revision, array('threatDeletionsAsExceptions' => true));
                            } catch (DeletedException $e) {
                                $value = null;
                            } catch (NoRevisionFoundException $e) {
                                // The entity does not have any revision yet. So let's get the actual state of it.
                                $value = $this->em->getRepository($targetClass->name)->findOneBy($pf);
                            }

                            $class->reflFields[$field]->setValue($entity, $value);
                        }
                    } else {
                        $class->reflFields[$field]->setValue($entity, null);
                    }
                } else {
                    if ($this->loadNativeEntities) {
                        if ($assoc['isOwningSide']) {
                            $associatedId = array();
                            foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                                $joinColumnValue = isset($data[$columnMap[$srcColumn]]) ? $data[$columnMap[$srcColumn]] : null;
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
                    } else {
                        $class->reflFields[$field]->setValue($entity, null);
                    }
                }
            } elseif ($assoc['type'] & ClassMetadata::ONE_TO_MANY) {
                if ($this->metadataFactory->isAudited($assoc['targetEntity'])) {
                    if ($this->loadAuditedCollections) {
                        $foreignKeys = array();
                        foreach ($targetClass->associationMappings[$assoc['mappedBy']]['sourceToTargetKeyColumns'] as $local => $foreign) {
                            $field = $class->getFieldForColumn($foreign);
                            $foreignKeys[$local] = $class->reflFields[$field]->getValue($entity);
                        }

                        $collection = new AuditedCollection($this, $targetClass->name, $targetClass, $assoc, $foreignKeys, $revision);

                        $class->reflFields[$assoc['fieldName']]->setValue($entity, $collection);
                    } else {
                        $class->reflFields[$assoc['fieldName']]->setValue($entity, new ArrayCollection());
                    }
                } else {
                    if ($this->loadNativeCollections) {
                        $collection = new PersistentCollection($this->em, $targetClass, new ArrayCollection());

                        $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToManyCollection($assoc, $entity, $collection);

                        $class->reflFields[$assoc['fieldName']]->setValue($entity, $collection);
                    } else {
                        $class->reflFields[$assoc['fieldName']]->setValue($entity, new ArrayCollection());
                    }
                }
            } else {
                // Inject collection
                $reflField = $class->reflFields[$field];
                $reflField->setValue($entity, new ArrayCollection);
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
            /** @var ClassMetadataInfo|ClassMetadata $class */
            $class = $this->em->getClassMetadata($className);

            if ($class->isInheritanceTypeSingleTable() && count($class->subClasses) > 0) {
                continue;
            }

            $tableName = $this->config->getTableName($class);
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
                $columnList .= ', ' . $type->convertToPHPValueSQL(
                        $tableAlias . '.' . $this->quoteStrategy->getColumnName($field, $class, $this->platform), $this->platform
                    ) . ' AS ' . $this->platform->quoteSingleIdentifier($field);
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
            } elseif ($class->isInheritanceTypeJoined() && $class->rootEntityName != $class->name) {
                $columnList .= ', re.' . $class->discriminatorColumn['name'];

                /** @var ClassMetadataInfo|ClassMetadata $rootClass */
                $rootClass = $this->em->getClassMetadata($class->rootEntityName);
                $rootTableName = $this->config->getTableName($rootClass);

                $joinSql = "INNER JOIN {$rootTableName} re ON";
                $joinSql .= " re.".$this->config->getRevisionFieldName()." = e.".$this->config->getRevisionFieldName();
                foreach ($class->getIdentifierColumnNames() as $name) {
                    $joinSql .= " AND re.$name = e.$name";
                }
            }

            $query = "SELECT " . $columnList . " FROM " . $tableName . " e " . $joinSql . " WHERE " . $whereSQL;
            $revisionsData = $this->em->getConnection()->executeQuery($query, $params);

            foreach ($revisionsData AS $row) {
                $id   = array();

                foreach ($class->identifier AS $idField) {
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

        /** @var ClassMetadataInfo|ClassMetadata $class */
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($class);

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

        /** @var ClassMetadataInfo|ClassMetadata $class */
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($class);

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
        /** @var ClassMetadataInfo|ClassMetadata $metadata */
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

        /** @var ClassMetadataInfo|ClassMetadata $class */
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTableName($class);

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
                    $this->quoteStrategy->getColumnName($field, $class, $this->platform),
                    $this->platform
                ) . ' AS ' . $this->platform->quoteSingleIdentifier($field);
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

        $query = "SELECT " . implode(', ', $columnList) . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.".$this->config->getRevisionFieldName()." DESC";
        $stmt = $this->em->getConnection()->executeQuery($query, $values);

        $result = array();
        while ($row = $stmt->fetch(Query::HYDRATE_ARRAY)) {
            $rev = $row[$this->config->getRevisionFieldName()];
            unset($row[$this->config->getRevisionFieldName()]);
            $result[] = $this->createEntity($class->name, $columnMap, $row, $rev);
        }

        return $result;
    }
}
