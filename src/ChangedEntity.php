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

class ChangedEntity
{
    /**
     * @var string
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
     */
    private $entity;

    public function __construct(string $className, array $id, string $revType, object $entity)
    {
        $this->className = $className;
        $this->id = $id;
        $this->revType = $revType;
        $this->entity = $entity;
    }

    /**
     * @return string
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
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
