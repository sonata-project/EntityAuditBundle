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

namespace SimpleThings\EntityAudit\Exception;

abstract class AuditException extends \Exception
{
    protected $className;

    protected $id;

    protected $revision;

    public function __construct($className, $id, $revision)
    {
        $this->className = $className;
        $this->id = $id;
        $this->revision = $revision;
    }
}
