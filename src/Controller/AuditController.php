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

namespace SimpleThings\EntityAudit\Controller;

use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\AuditReader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for listing auditing information.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class AuditController extends Controller
{
    /**
     * Renders a paginated list of revisions.
     *
     * @param int $page
     *
     * @return Response
     */
    public function indexAction($page = 1)
    {
        $reader = $this->getAuditReader();
        $revisions = $reader->findRevisionHistory(20, 20 * ($page - 1));

        return $this->render('@SimpleThingsEntityAudit/Audit/index.html.twig', [
            'revisions' => $revisions,
        ]);
    }

    /**
     * Shows entities changed in the specified revision.
     *
     * @param int $rev
     *
     * @throws NotFoundHttpException
     *
     * @return Response
     */
    public function viewRevisionAction($rev)
    {
        $revision = $this->getAuditReader()->findRevision($rev);
        if (!$revision) {
            throw $this->createNotFoundException(sprintf('Revision %i not found', $rev));
        }

        $changedEntities = $this->getAuditReader()->findEntitiesChangedAtRevision($rev);

        return $this->render('@SimpleThingsEntityAudit/Audit/view_revision.html.twig', [
            'revision' => $revision,
            'changedEntities' => $changedEntities,
        ]);
    }

    /**
     * Lists revisions for the supplied entity.
     *
     * @param string $className
     * @param string $id
     *
     * @return Response
     */
    public function viewEntityAction($className, $id)
    {
        $ids = explode(',', $id);
        $revisions = $this->getAuditReader()->findRevisions($className, $ids);

        return $this->render('@SimpleThingsEntityAudit/Audit/view_entity.html.twig', [
            'id' => $id,
            'className' => $className,
            'revisions' => $revisions,
        ]);
    }

    /**
     * Shows the data for an entity at the specified revision.
     *
     * @param string $className
     * @param string $id        Comma separated list of identifiers
     * @param int    $rev
     *
     * @return Response
     */
    public function viewDetailAction($className, $id, $rev)
    {
        $ids = explode(',', $id);
        $entity = $this->getAuditReader()->find($className, $ids, $rev);

        $data = $this->getAuditReader()->getEntityValues($className, $entity);
        krsort($data);

        return $this->render('@SimpleThingsEntityAudit/Audit/view_detail.html.twig', [
            'id' => $id,
            'rev' => $rev,
            'className' => $className,
            'entity' => $entity,
            'data' => $data,
        ]);
    }

    /**
     * Compares an entity at 2 different revisions.
     *
     * @param string   $className
     * @param string   $id        Comma separated list of identifiers
     * @param int|null $oldRev    if null, pulled from the query string
     * @param int|null $newRev    if null, pulled from the query string
     *
     * @return Response
     */
    public function compareAction(Request $request, $className, $id, $oldRev = null, $newRev = null)
    {
        if (null === $oldRev) {
            $oldRev = $request->query->get('oldRev');
        }

        if (null === $newRev) {
            $newRev = $request->query->get('newRev');
        }

        $ids = explode(',', $id);
        $diff = $this->getAuditReader()->diff($className, $ids, $oldRev, $newRev);

        return $this->render('@SimpleThingsEntityAudit/Audit/compare.html.twig', [
            'className' => $className,
            'id' => $id,
            'oldRev' => $oldRev,
            'newRev' => $newRev,
            'diff' => $diff,
        ]);
    }

    /**
     * @return AuditReader
     */
    protected function getAuditReader()
    {
        return $this->get('simplethings_entityaudit.reader');
    }

    /**
     * @return AuditManager
     */
    protected function getAuditManager()
    {
        return $this->get('simplethings_entityaudit.manager');
    }
}
