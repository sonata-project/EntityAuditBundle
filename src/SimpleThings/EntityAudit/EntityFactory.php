<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use SimpleThings\EntityAudit\Collection\AuditedCollection;
use SimpleThings\EntityAudit\Exception\DeletedException;
use SimpleThings\EntityAudit\Exception\NoRevisionFoundException;
use SimpleThings\EntityAudit\Exception\NotAuditedException;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

/**
 * @internal
 */
final class EntityFactory
{
    /**
     * Entity cache to prevent circular references
     *
     * @var array
     */
    private $entityCache;

    /**
     * @var AuditReader
     */
    private $auditReader;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $options;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(
        AuditReader $auditReader,
        EntityManagerInterface $em,
        MetadataFactory $metadataFactory,
        array $options = []
    ) {
        $this->auditReader = $auditReader;
        $this->em = $em;
        $this->metadataFactory = $metadataFactory;

        $this->options = array_merge([
            AuditReader::LOAD_AUDITED_COLLECTIONS => true,
            AuditReader::LOAD_AUDITED_ENTITIES => true,
            AuditReader::LOAD_NATIVE_COLLECTIONS => true,
            AuditReader::LOAD_NATIVE_ENTITIES => true,
        ], $options);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * @param string $className
     * @param array  $columnMap
     * @param array  $data
     * @param int    $revision
     * @param array  $options
     *
     * @return object
     *
     * @throws DeletedException
     * @throws NoRevisionFoundException
     * @throws NotAuditedException
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Exception
     */
    public function createEntity($className, array $columnMap, array $data, $revision, array $options = [])
    {
        $options = array_merge($this->options, $options);

        $classMetadata = $this->em->getClassMetadata($className);
        $cacheKey = $this->createEntityCacheKey($classMetadata, $data, $revision);

        if (isset($this->entityCache[$cacheKey])) {
            return $this->entityCache[$cacheKey];
        }

        if (! $classMetadata->isInheritanceTypeNone()) {
            if (! isset($data[$classMetadata->discriminatorColumn['name']])) {
                throw new \RuntimeException('Expecting discriminator value in data set.');
            }
            $discriminator = $data[$classMetadata->discriminatorColumn['name']];
            if (! isset($classMetadata->discriminatorMap[$discriminator])) {
                throw new \RuntimeException("No mapping found for [{$discriminator}].");
            }

            if ($classMetadata->discriminatorValue) {
                $entity = $this->em->getClassMetadata($classMetadata->discriminatorMap[$discriminator])->newInstance();
            } else {
                //a complex case when ToOne binding is against AbstractEntity having no discriminator
                $pk = array();

                foreach ($classMetadata->identifier as $field) {
                    $pk[$classMetadata->getColumnName($field)] = $data[$field];
                }

                return $this->auditReader->find($classMetadata->discriminatorMap[$discriminator], $pk, $revision);
            }
        } else {
            $entity = $classMetadata->newInstance();
        }

        //cache the entity to prevent circular references
        $this->entityCache[$cacheKey] = $entity;

        $connection = $this->em->getConnection();
        foreach ($data as $field => $value) {
            if (isset($classMetadata->fieldMappings[$field])) {
                $value = $connection->convertToPHPValue($value, $classMetadata->fieldMappings[$field]['type']);
                $classMetadata->reflFields[$field]->setValue($entity, $value);
            }
        }

        foreach ($classMetadata->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetched'][$className][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);
            $isAudited = $this->metadataFactory->isAudited($assoc['targetEntity']);

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                $value = null;

                if ($isAudited && $options[AuditReader::LOAD_AUDITED_ENTITIES]) {
                    // Primary Key. Used for audit tables queries.
                    $pk = array();
                    // Primary Field. Used when fallback to Doctrine finder.
                    $pf = array();

                    if ($assoc['isOwningSide']) {
                        foreach ($assoc['targetToSourceKeyColumns'] as $foreign => $local) {
                            $pk[$foreign] = $pf[$foreign] = $data[$columnMap[$local]];
                        }
                    } else {
                        $otherEntityAssoc = $this->em->getClassMetadata($assoc['targetEntity'])
                            ->associationMappings[$assoc['mappedBy']];

                        foreach ($otherEntityAssoc['targetToSourceKeyColumns'] as $local => $foreign) {
                            $pk[$foreign] = $pf[$otherEntityAssoc['fieldName']] = $data[$classMetadata->getFieldName($local)];
                        }
                    }

                    $pk = array_filter($pk);

                    if (! empty($pk)) {
                        try {
                            $value = $this->auditReader->find($targetClass->name, $pk, $revision, array_merge(
                                $options,
                                ['threatDeletionsAsExceptions' => true]
                            ));
                        } catch (DeletedException $e) {
                            $value = null;
                        } catch (NoRevisionFoundException $e) {
                            // The entity does not have any revision yet. So let's get the actual state of it.
                            $value = $this->em->getRepository($targetClass->name)->findOneBy($pf);
                        }
                    }
                } elseif (! $isAudited && $options[AuditReader::LOAD_NATIVE_ENTITIES]) {
                    if ($assoc['isOwningSide']) {
                        $associatedId = array();
                        foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                            $joinColumnValue = isset($data[$columnMap[$srcColumn]]) ? $data[$columnMap[$srcColumn]] : null;
                            if ($joinColumnValue !== null) {
                                $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                            }
                        }

                        if (! empty($associatedId)) {
                            $value = $this->em->getReference($targetClass->name, $associatedId);
                        }
                    } else {
                        // Inverse side of x-to-one can never be lazy
                        $value = $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToOneEntity($assoc, $entity);
                    }
                }

                $classMetadata->reflFields[$field]->setValue($entity, $value);
            } elseif ($assoc['type'] & ClassMetadata::ONE_TO_MANY) {
                $collection = new ArrayCollection();

                if ($isAudited && $options[AuditReader::LOAD_AUDITED_COLLECTIONS]) {
                    $foreignKeys = array();
                    foreach ($targetClass->associationMappings[$assoc['mappedBy']]['sourceToTargetKeyColumns'] as $local => $foreign) {
                        $field = $classMetadata->getFieldForColumn($foreign);
                        $foreignKeys[$local] = $classMetadata->reflFields[$field]->getValue($entity);
                    }

                    $collection = new AuditedCollection(
                        $this->auditReader,
                        $targetClass,
                        $assoc,
                        $foreignKeys,
                        $revision
                    );
                } elseif (! $isAudited && $options[AuditReader::LOAD_NATIVE_COLLECTIONS]) {
                    $collection = new PersistentCollection($this->em, $targetClass, new ArrayCollection());

                    $this->getEntityPersister($assoc['targetEntity'])
                        ->loadOneToManyCollection($assoc, $entity, $collection);
                }

                $classMetadata->reflFields[$assoc['fieldName']]->setValue($entity, $collection);
            } else {
                // Inject collection
                $classMetadata->reflFields[$field]->setValue($entity, new ArrayCollection());
            }
        }

        return $entity;
    }

    /**
     * Clears entity cache. Call this if you are fetching subsequent revisions using same AuditManager.
     */
    public function clearEntityCache()
    {
        $this->entityCache = [];
    }

    /**
     * @param object $entity
     *
     * @return \Doctrine\ORM\Persisters\Entity\EntityPersister
     */
    private function getEntityPersister($entity)
    {
        return $this->em->getUnitOfWork()->getEntityPersister($entity);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param array         $data
     * @param int           $revision
     *
     * @return string
     */
    private function createEntityCacheKey(ClassMetadata $classMetadata, array $data, $revision)
    {
        $keyParts = array();

        foreach ($classMetadata->getIdentifierFieldNames() as $name) {
            if ($classMetadata->hasAssociation($name)) {
                if ($classMetadata->isSingleValuedAssociation($name)) {
                    $name = $classMetadata->getSingleAssociationJoinColumnName($name);
                } else {
                    // Doctrine should throw a mapping exception if an identifier
                    // that is an association is not single valued, but just in case.
                    throw new \RuntimeException('Multiple valued association identifiers not supported');
                }
            }
            $keyParts[] = $data[$name];
        }

        return $classMetadata->name . '_' . implode('_', $keyParts) . '_' . $revision;
    }
}
