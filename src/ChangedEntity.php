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
     * @var string
     *
     * @phpstan-var class-string<T>
     */
    private $className;

    /**
     * @var array
     */
    private $id;

    /**
     * @var string
     */
    private $revType;

    /**
     * @var object
     *
     * @phpstan-var T
     */
    private $entity;

    /**
     * @phpstan-param class-string<T> $className
     * @phpstan-param T $entity
     */
    public function __construct(string $className, array $id, string $revType, object $entity)
    {
        $this->className = $className;
        $this->id = $id;
        $this->revType = $revType;
        $this->entity = $entity;
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
     * @return array
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
