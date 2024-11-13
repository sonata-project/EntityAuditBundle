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

final class IndexAction
{
    public function __construct(
        private Environment $twig,
        private AuditReader $auditReader,
    ) {
    }

    public function __invoke(int $page = 1): Response
    {
        $revisions = $this->auditReader->findRevisionHistory(20, 20 * ($page - 1));

        $content = $this->twig->render('@SimpleThingsEntityAudit/Audit/index.html.twig', [
            'revisions' => $revisions,
        ]);

        return new Response($content);
    }
}
