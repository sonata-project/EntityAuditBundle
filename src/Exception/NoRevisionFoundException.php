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

class NoRevisionFoundException extends AuditException
{
    public function __construct($className, $id, $revision)
    {
        parent::__construct($className, $id, $revision);
        $this->message = sprintf(
            "No revision of class '%s' (%s) was found at revision %s or before. The entity did not exist at the specified revision yet.",
            $className,
            implode(', ', $id),
            $revision
        );
    }
}
