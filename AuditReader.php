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

use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class AuditReader
{
    private $em;

    private $config;

    private $metadataFactory;

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
    }

    /**
     * Find a class at the specific revision. 
     * 
     * This method does not require the revision to be exact but it also searches for an earlier revision
     * of this entity and always returns the latest revision below or equal the given revision
     * 
     * @param string $className
     * @param mixed $id
     * @param int $revision 
     * @return object
     */
    public function find($className, $id, $revision)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }
        
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
        
        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }
        
        $whereSQL = "e." . $this->config->getRevisionTypeFieldName() ." <= ?";
        foreach ($class->identifier AS $idField => $value) {
            if (isset($class->fieldMappings[$idField])) {
                $whereSQL .= " AND " . $class->fieldMappings[$idField]['column'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                $whereSQL .= " AND " . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }
        
        $columnList = "";
        foreach ($class->fieldNames AS $field) {
            if ($columnList) {
                $columnList .= ', ';
            }
            $columnList .= $class->getQuotedColumnName($field, $this->platform) .' AS ' . $field;
        }
        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                    if ($columnList) {
                        $columnList .= ', ';
                    }
                    $columnList .= $sourceCol;
                }
            }
        }
        
        $values = array_merge(array($revision), array_values($id));
        
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.rev DESC";
        $platform->modifyLimitQuery($query, 1);
        $revision = $this->em->getConnection()->executeQuery($query, $values);
        
        if ($revision) {
            return $this->createEntity($class->name, $revision[0]);
        } else {
            throw AuditException::noRevisionFound($class->name, $id, $revision);
        }
    }
    
    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     * 
     * NOTICE: Creates an old version of the entity, HOWEVER related associations are all managed entities!!
     * 
     * @param string $className
     * @param array $data
     * @return object
     */
    private function createEntity($className, array $data)
    {
        $class = $this->em->getClassMetadata($className);
        $entity = $class->newInstance();

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
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
                if ($assoc['isOwningSide']) {
                    $associatedId = array();
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;
                        if ($joinColumnValue !== null) {
                            $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                        }
                    }
                    if ( ! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                    } else {
                        $associatedEntity = $this->em->getReference($targetClass->name, $associatedId);
                        $class->reflFields[$field]->setValue($entity, $associatedEntity);
                    }
                } else {
                    // Inverse side of x-to-one can never be lazy
                    $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToOneEntity($assoc, $entity, null));
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
        $platform = $this->em->getConnection()->getDatabasePlatform();
        
        $query = $platform->modifyLimitQuery("SELECT * FROM " . $this->config->getRevisionTableName(), $limit, $offset);
        $revisionsData = $this->em->getConnection()->executeQuery($query);
        
        $revisions = array();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                DateTime::createFromFormat($platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username'],
                $row['change_comment']
            );
        }
        return $revisions;
    }
    
    public function findEntitesChangedAtRevision($revision)
    {        
        $auditedEntities = $this->metadataFactory->getAllClassNames();
        
        $changedEntities = array();
        foreach ($auditedEntities AS $className) {
            $class = $this->em->getClassMetadata($className);
            $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
            
            $whereSQL = "e." . $this->config->getRevisionTypeFieldName() ." = ?";
            $columnList = "e." . $this->config->getRevisionTypeFieldName() .", ";
            foreach ($class->fieldNames AS $field) {
                $columnList .= ', ' . $class->getQuotedColumnName($field, $this->platform) .' AS ' . $field;
            }
            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columnList .= ', ' . $sourceCol;
                    }
                }
            }

            $platform = $this->em->getConnection()->getDatabasePlatform();
            $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL;
            $revisionsData = $this->em->getConnection()->executeQuery($query, array($revision));

            foreach ($revisionsData AS $row) {
                $id = array();
                foreach ($class->identifier AS $idField) {
                    // TODO: doesnt work with composite foreign keys yet.
                    $id[$idField] = $row[$idField];
                }
                
                $entity = $this->createEntity($className, $row);
                $changedEntities[] = new ChangedEntity($className, $id, $row[$this->config->getRevisionTypeFieldName()], $entity);
            }
        }
        return $changedEntities;
    }

    /**
     * Find all revisions that were made of entity class with given id.
     * 
     * @param string $className
     * @param mixed $id
     * @return Revision[]
     */
    public function findRevisions($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }
        
        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
        
        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }
        
        $whereSQL = "";
        foreach ($class->identifier AS $idField => $value) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= $class->fieldMappings[$idField]['column'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }
        
        $query = "SELECT r.* FROM " . $this->config->getRevisionTableName() . " r " . 
                 "INNER JOIN " . $tableName . " e ON r.id = e." . $this->config->getRevisionFieldName() . " WHERE " . $whereSQL . " ORDER BY r.id ASC";
        $revisionsData = $this->em->getConnection()->executeQuery($query, array_values($id));
        
        $revisions = array();
        $platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                DateTime::createFromFormat($platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username'],
                $row['change_comment']
            );
        }
        
        return $revisions;
    }
}