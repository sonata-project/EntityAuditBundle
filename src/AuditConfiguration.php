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

use Doctrine\ORM\Mapping\ClassMetadataInfo;

class AuditConfiguration
{
    private $auditedEntityClasses = [];
    private $globalIgnoreColumns = [];
    private $entityIgnoredProperties = [];
    private $tablePrefix = '';
    private $tableSuffix = '_audit';
    private $revisionTableName = 'revisions';
    private $revisionFieldName = 'rev';
    private $revisionTypeFieldName = 'revtype';
    private $revisionIdFieldType = 'integer';
    private $usernameCallable;
    private $convertEnumToString = false;

    /**
     * @return AuditConfiguration
     */
    public static function forEntities(array $classes)
    {
        $conf = new self();
        $conf->auditedEntityClasses = $classes;

        return $conf;
    }

    /**
     * @return string
     */
    public function getTableName(ClassMetadataInfo $metadata)
    {
        $tableName = $metadata->getTableName();

        //## Fix for doctrine/orm >= 2.5
        if (method_exists($metadata, 'getSchemaName') && $metadata->getSchemaName()) {
            $tableName = $metadata->getSchemaName().'.'.$tableName;
        }

        return $this->getTablePrefix().$tableName.$this->getTableSuffix();
    }

    /**
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * @param string $prefix
     */
    public function setTablePrefix($prefix): void
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * @return string
     */
    public function getTableSuffix()
    {
        return $this->tableSuffix;
    }

    /**
     * @param string $suffix
     */
    public function setTableSuffix($suffix): void
    {
        $this->tableSuffix = $suffix;
    }

    /**
     * @return string
     */
    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    /**
     * @param string $revisionFieldName
     */
    public function setRevisionFieldName($revisionFieldName): void
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    /**
     * @return string
     */
    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    /**
     * @param string $revisionTypeFieldName
     */
    public function setRevisionTypeFieldName($revisionTypeFieldName): void
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    /**
     * @return string
     */
    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    /**
     * @param string $revisionTableName
     */
    public function setRevisionTableName($revisionTableName): void
    {
        $this->revisionTableName = $revisionTableName;
    }

    /**
     * @param string[] $classes
     */
    public function setAuditedEntityClasses(array $classes): void
    {
        $this->auditedEntityClasses = $classes;
    }

    /**
     * @return string[]
     */
    public function getGlobalIgnoreColumns()
    {
        return $this->globalIgnoreColumns;
    }

    /**
     * @param string[] $columns
     */
    public function setGlobalIgnoreColumns(array $columns): void
    {
        $this->globalIgnoreColumns = $columns;
    }

    /**
     * @return Metadata\MetadataFactory
     */
    public function createMetadataFactory()
    {
        return new Metadata\MetadataFactory($this->auditedEntityClasses);
    }

    /**
     * @deprecated
     *
     * @param string|null $username
     */
    public function setCurrentUsername($username): void
    {
        $this->setUsernameCallable(static function () use ($username) {
            return $username;
        });
    }

    /**
     * @return string
     */
    public function getCurrentUsername()
    {
        $callable = $this->usernameCallable;

        return (string) ($callable ? $callable() : '');
    }

    /**
     * @param callable|null $usernameCallable
     */
    public function setUsernameCallable($usernameCallable): void
    {
        // php 5.3 compat
        if (null !== $usernameCallable && !\is_callable($usernameCallable)) {
            throw new \InvalidArgumentException(sprintf('Username Callable must be callable. Got: %s', \is_object($usernameCallable) ? \get_class($usernameCallable) : \gettype($usernameCallable)));
        }

        $this->usernameCallable = $usernameCallable;
    }

    /**
     * @return callable|null
     */
    public function getUsernameCallable()
    {
        return $this->usernameCallable;
    }

    /**
     * @param string $revisionIdFieldType
     */
    public function setRevisionIdFieldType($revisionIdFieldType): void
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    /**
     * @return string
     */
    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }

    /**
     * @return array<string, string[]>
     */
    public function getEntityIgnoredProperties(): array
    {
        return $this->entityIgnoredProperties;
    }

    /**
     * @param array<string, string[]> $fields
     */
    public function setEntityIgnoredProperties(array $fields): void
    {
        $this->entityIgnoredProperties = $fields;
    }

    public function isEntityIgnoredProperty(string $entity, $propertyName): bool
    {
        return \array_key_exists($entity, $this->getEntityIgnoredProperties()) && \in_array($propertyName, $this->getEntityIgnoredProperties()[$entity], true);
    }

    public function setConvertEnumToString(bool $convertEnum): void
    {
        $this->convertEnumToString = $convertEnum;
    }

    public function getConvertEnumToString(): bool
    {
        return $this->convertEnumToString;
    }
}
