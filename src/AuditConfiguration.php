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
     * @param string[] $classes
     */
    public static function forEntities(array $classes): self
    {
        $conf = new self();
        $conf->auditedEntityClasses = $classes;

        return $conf;
    }

    public function getTableName(ClassMetadataInfo $metadata): string
    {
        $tableName = $metadata->getTableName();

        //## Fix for doctrine/orm >= 2.5
        if (method_exists($metadata, 'getSchemaName') && $metadata->getSchemaName()) {
            $tableName = $metadata->getSchemaName().'.'.$tableName;
        }

        return $this->getTablePrefix().$tableName.$this->getTableSuffix();
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string $prefix): void
    {
        $this->tablePrefix = $prefix;
    }

    public function getTableSuffix(): string
    {
        return $this->tableSuffix;
    }

    public function setTableSuffix(string $suffix): void
    {
        $this->tableSuffix = $suffix;
    }

    public function getRevisionFieldName(): string
    {
        return $this->revisionFieldName;
    }

    public function setRevisionFieldName(string $revisionFieldName): void
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    public function setRevisionTypeFieldName(string $revisionTypeFieldName): void
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    public function getRevisionTableName(): string
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName(string $revisionTableName): void
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
    public function getGlobalIgnoreColumns(): array
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

    public function createMetadataFactory(): Metadata\MetadataFactory
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

    public function getCurrentUsername(): string
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

    public function setRevisionIdFieldType(string $revisionIdFieldType): void
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    public function getRevisionIdFieldType(): string
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

    public function convertEnumToString(): bool
    {
        return $this->convertEnumToString;
    }
}
