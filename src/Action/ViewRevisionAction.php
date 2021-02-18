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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ViewRevisionAction
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

    public function __invoke(int $rev): Response
    {
        $revision = $this->auditReader->findRevision($rev);
        if (!$revision) {
            throw new NotFoundHttpException(sprintf('Revision %i not found', $rev));
        }

        $changedEntities = $this->auditReader->findEntitiesChangedAtRevision($rev);

        $content = $this->twig->render('@SimpleThingsEntityAudit/Audit/view_revision.html.twig', [
            'revision' => $revision,
            'changedEntities' => $changedEntities,
        ]);

        return new Response($content);
    }
}
