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

/**
 * @phpstan-template T of object
 */
class ChangedEntity
{
    /**
     * @param array<string, int|string> $id
     *
     * @phpstan-param class-string<T> $className
     * @phpstan-param T $entity
     */
    public function __construct(
        private string $className,
        private array $id,
        private string $revType,
        private object $entity,
    ) {
    }

    /**
     * @return string
     *
     * @phpstan-return class-string<T>
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return array<string, int|string>
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRevisionType()
    {
        return $this->revType;
    }

    /**
     * @return object
     *
     * @phpstan-return T
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
