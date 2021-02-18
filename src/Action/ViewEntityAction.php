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

namespace SimpleThings\EntityAudit\Action;

use SimpleThings\EntityAudit\AuditReader;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ViewEntityAction
{
    /**
     * @var AuditReader
     */
    private $auditReader;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig, AuditReader $auditReader)
    {
        $this->twig = $twig;
        $this->auditReader = $auditReader;
    }

    public function __invoke(string $className, string $id): Response
    {
        $ids = explode(',', $id);
        $revisions = $this->auditReader->findRevisions($className, $ids);

        $content = $this->twig->render('@SimpleThingsEntityAudit/Audit/view_entity.html.twig', [
            'id' => $id,
            'className' => $className,
            'revisions' => $revisions,
        ]);

        return new Response($content);
    }
}
