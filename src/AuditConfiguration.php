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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class AuditConfiguration
{
    /**
     * @var string[]
     *
     * @phpstan-var class-string[]
     */
    private array $auditedEntityClasses = [];

    private bool $disableForeignKeys = false;

    /**
     * @var string[]
     */
    private array $globalIgnoreColumns = [];

    /** @phpstan-var literal-string  */
    private string $tablePrefix = '';

    /** @phpstan-var literal-string */
    private string $tableSuffix = '_audit';

    /** @phpstan-var literal-string */
    private string $revisionTableName = 'revisions';

    /** @phpstan-var literal-string */
    private string $revisionFieldName = 'rev';

    /** @phpstan-var literal-string */
    private string $revisionTypeFieldName = 'revtype';

    private string $revisionIdFieldType = Types::INTEGER;

    /**
     * @var callable|null
     */
    private $usernameCallable;

    /**
     * @param string[] $classes
     *
     * @return AuditConfiguration
     *
     * @phpstan-param class-string[] $classes
     */
    public static function forEntities(array $classes)
    {
        $conf = new self();
        $conf->auditedEntityClasses = $classes;

        return $conf;
    }

    /**
     * @param ClassMetadata<object> $metadata
     *
     * @return string
     *
     * @phpstan-return literal-string
     *
     * @psalm-suppress MoreSpecificReturnType,LessSpecificReturnStatement https://github.com/vimeo/psalm/issues/10910
     */
    public function getTableName(ClassMetadata $metadata)
    {
        /** @var literal-string $tableName */
        $tableName = $metadata->getTableName();
        /** @var literal-string|null $schemaName */
        $schemaName = $metadata->getSchemaName();
        if (null !== $schemaName && '' !== $schemaName) {
            $tableName = $schemaName.'.'.$tableName;
        }

        return $this->getTablePrefix().$tableName.$this->getTableSuffix();
    }

    public function areForeignKeysDisabled(): bool
    {
        return $this->disableForeignKeys;
    }

    public function setDisabledForeignKeys(bool $disabled): void
    {
        $this->disableForeignKeys = $disabled;
    }

    /**
     * @return string
     *
     * @phpstan-return literal-string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * @param string $prefix
     *
     * @phpstan-param literal-string $prefix
     */
    public function setTablePrefix($prefix): void
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * @return string
     *
     * @phpstan-return literal-string
     */
    public function getTableSuffix()
    {
        return $this->tableSuffix;
    }

    /**
     * @param string $suffix
     *
     * @phpstan-param literal-string $suffix
     */
    public function setTableSuffix($suffix): void
    {
        $this->tableSuffix = $suffix;
    }

    /**
     * @return string
     *
     * @phpstan-return literal-string
     */
    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    /**
     * @param string $revisionFieldName
     *
     * @phpstan-param literal-string $revisionFieldName
     */
    public function setRevisionFieldName($revisionFieldName): void
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    /**
     * @return string
     *
     * @phpstan-return literal-string
     */
    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    /**
     * @param string $revisionTypeFieldName
     *
     * @phpstan-param literal-string $revisionTypeFieldName
     */
    public function setRevisionTypeFieldName($revisionTypeFieldName): void
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    /**
     * @return string
     *
     * @phpstan-return literal-string
     */
    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    /**
     * @param string $revisionTableName
     *
     * @phpstan-param literal-string $revisionTableName
     */
    public function setRevisionTableName($revisionTableName): void
    {
        $this->revisionTableName = $revisionTableName;
    }

    /**
     * @param string[] $classes
     *
     * @phpstan-param class-string[] $classes
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
     * @return MetadataFactory
     */
    public function createMetadataFactory()
    {
        return new MetadataFactory($this->auditedEntityClasses);
    }

    /**
     * @deprecated
     *
     * @param string|null $username
     */
    public function setCurrentUsername($username): void
    {
        $this->setUsernameCallable(static fn () => $username);
    }

    /**
     * @return string
     */
    public function getCurrentUsername()
    {
        $callable = $this->usernameCallable;

        return null !== $callable ? (string) $callable() : '';
    }

    /**
     * @param callable|null $usernameCallable
     */
    public function setUsernameCallable($usernameCallable): void
    {
        // php 5.3 compat
        if (null !== $usernameCallable && !\is_callable($usernameCallable)) {
            throw new \InvalidArgumentException(\sprintf('Username Callable must be callable. Got: %s', get_debug_type($usernameCallable)));
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
}
