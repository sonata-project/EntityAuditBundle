<?php

namespace SimpleThings\EntityAudit;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Configuration;

class AuditConfigurationFactory
{
    const ANNOTATION_AUDITABLE = 'SimpleThings\\EntityAudit\\Mapping\\Annotation\\Auditable';

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
