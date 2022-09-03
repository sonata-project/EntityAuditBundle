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

use SimpleThings\EntityAudit\Action\CompareAction;
use SimpleThings\EntityAudit\Action\IndexAction;
use SimpleThings\EntityAudit\Action\ViewDetailAction;
use SimpleThings\EntityAudit\Action\ViewEntityAction;
use SimpleThings\EntityAudit\Action\ViewRevisionAction;
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
 *
 * @deprecated since sonata-project/entity-audit-bundle 1.1, will be remove in 2.0.
 *
 * NEXT_MAJOR: remove this controller
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
        $indexAction = new IndexAction($this->get('twig'), $this->getAuditReader());

        return $indexAction($page);
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
        $viewRevisionAction = new ViewRevisionAction($this->get('twig'), $this->getAuditReader());

        return $viewRevisionAction($rev);
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
        $viewEntityAction = new ViewEntityAction($this->get('twig'), $this->getAuditReader());

        return $viewEntityAction($className, $id);
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
        $viewDetailAction = new ViewDetailAction($this->get('twig'), $this->getAuditReader());

        return $viewDetailAction($className, $id, $rev);
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
        $compareAction = new CompareAction($this->get('twig'), $this->getAuditReader());

        return $compareAction($request, $className, $id, $oldRev, $newRev);
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
