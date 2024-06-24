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

namespace SimpleThings\EntityAudit\Utils;

use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMapping;
use Doctrine\ORM\Mapping\EmbeddedClassMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\JoinColumnMapping;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;

/**
 * @internal
 */
trait ORMCompatibilityTrait
{
    /**
     * @param array<string, mixed>|AssociationMapping|EmbeddedClassMapping|FieldMapping|JoinColumnMapping|DiscriminatorColumnMapping $mapping
     */
    final protected static function getMappingValue(array|AssociationMapping|EmbeddedClassMapping|FieldMapping|JoinColumnMapping|DiscriminatorColumnMapping $mapping, string $key): mixed
    {
        if ($mapping instanceof AssociationMapping || $mapping instanceof EmbeddedClassMapping || $mapping instanceof FieldMapping || $mapping instanceof JoinColumnMapping || $mapping instanceof DiscriminatorColumnMapping) {
            /* @phpstan-ignore property.dynamicName */
            return $mapping->$key;
        }

        return $mapping[$key] ?? null;
    }

    /**
     * @param array<string, mixed>|AssociationMapping|FieldMapping|DiscriminatorColumnMapping $mapping
     *
     * @return literal-string
     */
    final protected static function getMappingFieldNameValue(array|AssociationMapping|EmbeddedClassMapping|FieldMapping|DiscriminatorColumnMapping $mapping): string
    {
        if ($mapping instanceof AssociationMapping || $mapping instanceof FieldMapping || $mapping instanceof DiscriminatorColumnMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->fieldName;
        }

        /* @phpstan-ignore return.type */
        return $mapping['fieldName'];
    }

    /**
     * @param array<string, mixed>|JoinColumnMapping|DiscriminatorColumnMapping $mapping
     *
     * @return literal-string
     */
    final protected static function getMappingNameValue(array|JoinColumnMapping|DiscriminatorColumnMapping $mapping): string
    {
        if ($mapping instanceof JoinColumnMapping || $mapping instanceof DiscriminatorColumnMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->name;
        }

        /* @phpstan-ignore return.type */
        return $mapping['name'];
    }

    /**
     * @param array<string, mixed>|FieldMapping $mapping
     *
     * @return literal-string
     */
    final protected static function getMappingColumnNameValue(array|FieldMapping $mapping): string
    {
        if ($mapping instanceof FieldMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->columnName;
        }

        /* @phpstan-ignore return.type */
        return $mapping['columnName'];
    }

    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $mapping
     *
     * @return literal-string
     */
    final protected static function getMappingJoinTableNameValue(array|ManyToManyOwningSideMapping $mapping): string
    {
        if ($mapping instanceof ManyToManyOwningSideMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->joinTable->name;
        }

        /* @phpstan-ignore return.type */
        return $mapping['joinTable']['name'];
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     *
     * @phpstan-assert-if-true ManyToManyOwningSideMapping $mapping
     */
    final protected static function isManyToManyOwningSideMapping(array|AssociationMapping $mapping): bool
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->isManyToMany() && $mapping->isOwningSide();
        }

        return true === $mapping['isOwningSide'] && ($mapping['type'] & ClassMetadata::MANY_TO_MANY) > 0;
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     *
     * @phpstan-assert-if-true ToOneOwningSideMapping $mapping
     */
    final protected static function isToOneOwningSide(array|AssociationMapping $mapping): bool
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->isToOneOwningSide();
        }

        return ($mapping['type'] & ClassMetadata::TO_ONE) > 0 && true === $mapping['isOwningSide'];
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     */
    final protected static function isToOne(array|AssociationMapping $mapping): bool
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->isToOne();
        }

        return ($mapping['type'] & ClassMetadata::TO_ONE) > 0;
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     */
    final protected static function isManyToMany(array|AssociationMapping $mapping): bool
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->isManyToMany();
        }

        return ($mapping['type'] & ClassMetadata::MANY_TO_MANY) > 0;
    }

    /**
     * @param array<string, mixed>|ToOneOwningSideMapping $mapping
     *
     * @return array<string, literal-string>
     */
    final protected static function getTargetToSourceKeyColumns(array|ToOneOwningSideMapping $mapping): array
    {
        if ($mapping instanceof ToOneOwningSideMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->targetToSourceKeyColumns;
        }

        return $mapping['targetToSourceKeyColumns'];
    }

    /**
     * @param array<string, mixed>|ToOneOwningSideMapping $mapping
     *
     * @return array<string, string>
     */
    final protected static function getSourceToTargetKeyColumns(array|ToOneOwningSideMapping $mapping): array
    {
        if ($mapping instanceof ToOneOwningSideMapping) {
            return $mapping->sourceToTargetKeyColumns;
        }

        return $mapping['sourceToTargetKeyColumns'];
    }

    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $mapping
     *
     * @return array<literal-string, literal-string>
     */
    final protected static function getRelationToSourceKeyColumns(array|ManyToManyOwningSideMapping $mapping): array
    {
        if ($mapping instanceof ManyToManyOwningSideMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->relationToSourceKeyColumns;
        }

        return $mapping['relationToSourceKeyColumns'];
    }

    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $mapping
     *
     * @return array<literal-string, literal-string>
     */
    final protected static function getRelationToTargetKeyColumns(array|ManyToManyOwningSideMapping $mapping): array
    {
        if ($mapping instanceof ManyToManyOwningSideMapping) {
            /* @phpstan-ignore return.type */
            return $mapping->relationToTargetKeyColumns;
        }

        return $mapping['relationToTargetKeyColumns'];
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     *
     * @phpstan-return class-string
     */
    final protected static function getMappingTargetEntityValue(array|AssociationMapping $mapping): string
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->targetEntity;
        }

        return $mapping['targetEntity'];
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     */
    final protected static function isOwningSide(array|AssociationMapping $mapping): bool
    {
        if ($mapping instanceof AssociationMapping) {
            return $mapping->isOwningSide();
        }

        return true === $mapping['isOwningSide'];
    }
}
