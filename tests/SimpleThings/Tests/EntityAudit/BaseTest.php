<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|null
     */
    protected static $sharedConn;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SchemaTool
     */
    protected $schemaTool;

    /**
     * @var AuditManager
     */
    protected $auditManager = null;

    protected $schemaEntities = array();

    protected $auditedEntities = array();

    public function setUp()
    {
        if (!isset(static::$sharedConn)) {
            static::$sharedConn = TestUtil::getConnection();
        }

        if (!$this->em) {
            $this->em = $this->getEntityManager();
            $this->schemaTool = new SchemaTool($this->em);
        }

        if (!$this->auditManager) {
            $this->auditManager = $this->getAuditManager();
        }

        $this->setUpEntitySchema();
    }

    public function tearDown()
    {
        $this->tearDownEntitySchema();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $config = new Configuration();
        $config->setMetadataCacheImpl(new ArrayCache());
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setProxyNamespace('SimpleThings\EntityAudit\Tests\Proxies');

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(
            realpath(__DIR__ . '/Fixtures/Core'),
            realpath(__DIR__ . '/Fixtures/Issue'),
            realpath(__DIR__ . '/Fixtures/Relation'),
        ), false));

        Gedmo\DoctrineExtensions::registerAnnotations();

        $conn = static::$sharedConn;

        // get rid of more global state
        $evm = $conn->getEventManager();
        foreach ($evm->getListeners() AS $event => $listeners) {
            foreach ($listeners AS $listener) {
                $evm->removeEventListener(array($event), $listener);
            }
        }

        return EntityManager::create($conn, $config);
    }

    /**
     * @return AuditManager
     */
    protected function getAuditManager()
    {
        $auditConfig = new AuditConfiguration();
        $auditConfig->setCurrentUsername('beberlei');
        $auditConfig->setAuditedEntityClasses($this->auditedEntities);
        $auditConfig->setGlobalIgnoreColumns(array('ignoreme'));

        $auditManager = new AuditManager($auditConfig);
        $auditManager->registerEvents(static::$sharedConn->getEventManager());

        return $auditManager;
    }

    protected function setUpEntitySchema()
    {
        $em = $this->em;
        $classes = array_map(function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->schemaTool->createSchema($classes);
    }

    protected function tearDownEntitySchema()
    {
        $em = $this->em;
        $classes = array_map(function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->schemaTool->dropSchema($classes);
    }
}
