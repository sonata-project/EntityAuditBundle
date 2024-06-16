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
     *
     * @phpstan-ignore-next-line
     */
    private static function getMappingValue(array|AssociationMapping|EmbeddedClassMapping|FieldMapping|JoinColumnMapping|DiscriminatorColumnMapping $mapping, string $key): mixed
    {
        /* @phpstan-ignore-next-line */
        if ($mapping instanceof AssociationMapping || $mapping instanceof EmbeddedClassMapping || $mapping instanceof FieldMapping || $mapping instanceof JoinColumnMapping || $mapping instanceof DiscriminatorColumnMapping) {
            /* @phpstan-ignore property.dynamicName */
            return $mapping->$key;
        }

        return $mapping[$key] ?? null;
    }

    /**
     * @param array<string, mixed>|ManyToManyOwningSideMapping $mapping
     *
     * @return literal-string
     *
     * @phpstan-ignore-next-line
     */
    private static function getJoinTableName(array|ManyToManyOwningSideMapping $mapping): string
    {
        /* @phpstan-ignore-next-line */
        if ($mapping instanceof ManyToManyOwningSideMapping) {
            /* @phpstan-ignore-next-line */
            return $mapping->joinTable->name;
        }

        /* @phpstan-ignore-next-line */
        return $mapping['joinTable']['name'];
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     */
    private static function isManyToManyOwningSideMapping(array|AssociationMapping $mapping): bool
    {
        /* @phpstan-ignore-next-line */
        if ($mapping instanceof AssociationMapping) {
            /* @phpstan-ignore-next-line */
            return $mapping instanceof ManyToManyOwningSideMapping;
        }

        /* @phpstan-ignore-next-line */
        return $mapping['isOwningSide'] && isset($mapping['joinTable']['name']);
    }

    /**
     * @param array<string, mixed>|AssociationMapping $mapping
     */
    private static function isToOneOwningSide(array|AssociationMapping $mapping): bool
    {
        /* @phpstan-ignore class.notFound */
        if ($mapping instanceof AssociationMapping) {
            /* @phpstan-ignore class.notFound */
            return $mapping instanceof ToOneOwningSideMapping;
        }

        return ($mapping['type'] & ClassMetadata::TO_ONE) > 0
            && true === $mapping['isOwningSide']
            && isset($mapping['targetToSourceKeyColumns']);
    }
}
