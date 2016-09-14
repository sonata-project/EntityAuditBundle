<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Configuration;

class AuditConfigurationFactory
{
    const ANNOTATION_AUDITABLE = 'SimpleThings\\EntityAudit\\Mapping\\Annotation\\Auditable';

    /**
     * This is the factory method used by the Symfony DependencyInjection
     *
     * @param Configuration $doctrineConfiguration
     * @param Reader $reader
     * @param array $auditedEntities
     * @return AuditConfiguration
     */
    public function createAuditConfiguration(Configuration $doctrineConfiguration, Reader $reader, $auditedEntities = array())
    {
        $entities = $doctrineConfiguration
            ->getMetadataDriverImpl()
            ->getAllClassNames();

        foreach ($entities as $entity) {
            if ($reader->getClassAnnotation(new \ReflectionClass($entity), self::ANNOTATION_AUDITABLE) && !in_array($entity, $auditedEntities)) {
                $auditedEntities[] = $entity;
            }
        }

        return AuditConfiguration::forEntities($auditedEntities);
    }
}
