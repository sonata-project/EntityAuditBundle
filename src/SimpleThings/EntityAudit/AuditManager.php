<?php

namespace SimpleThings\EntityAudit;

use Doctrine\ORM\EntityManagerInterface;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 */
class AuditManager
{
    /**
     * @var AuditConfiguration
     */
    private $config;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param AuditConfiguration     $config
     */
    public function __construct(EntityManagerInterface $entityManager, AuditConfiguration $config)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->metadataFactory = new Metadata\MetadataFactory($this->entityManager, $config->getMetadataDriver());
    }

    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function createAuditReader()
    {
        return new AuditReader($this->entityManager, $this->config, $this->metadataFactory);
    }

    /**
     * @param  EntityManagerInterface $entityManager
     * @return AuditManager
     */
    public static function create(EntityManagerInterface $entityManager)
    {
        return new self($entityManager, AuditConfiguration::createWithAnnotationDriver());
    }
}
