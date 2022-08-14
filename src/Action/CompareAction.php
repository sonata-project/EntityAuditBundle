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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class CompareAction
{
    private AuditReader $auditReader;

    private Environment $twig;

    public function __construct(Environment $twig, AuditReader $auditReader)
    {
        $this->twig = $twig;
        $this->auditReader = $auditReader;
    }

    /**
     * @phpstan-param class-string $className
     */
    public function __invoke(Request $request, string $className, string $id, ?int $oldRev = null, ?int $newRev = null): Response
    {
        if (null === $oldRev) {
            $oldRev = $request->query->get('oldRev');
            \assert(null !== $oldRev);
        }

        if (null === $newRev) {
            $newRev = $request->query->get('newRev');
            \assert(null !== $newRev);
        }

        $diff = $this->auditReader->diff($className, $id, $oldRev, $newRev);

        $content = $this->twig->render('@SimpleThingsEntityAudit/Audit/compare.html.twig', [
            'className' => $className,
            'id' => $id,
            'oldRev' => $oldRev,
            'newRev' => $newRev,
            'diff' => $diff,
        ]);

        return new Response($content);
    }
}
