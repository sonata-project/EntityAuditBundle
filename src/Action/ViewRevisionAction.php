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
use SimpleThings\EntityAudit\Exception\InvalidRevisionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class ViewRevisionAction
{
    public function __construct(
        private Environment $twig,
        private AuditReader $auditReader,
    ) {
    }

    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(int $rev): Response
    {
        try {
            $revision = $this->auditReader->findRevision($rev);
        } catch (InvalidRevisionException $ex) {
            throw new NotFoundHttpException(\sprintf('Revision %d not found', $rev), $ex);
        }

        $changedEntities = $this->auditReader->findEntitiesChangedAtRevision($rev);

        $content = $this->twig->render('@SimpleThingsEntityAudit/Audit/view_revision.html.twig', [
            'revision' => $revision,
            'changedEntities' => $changedEntities,
        ]);

        return new Response($content);
    }
}
