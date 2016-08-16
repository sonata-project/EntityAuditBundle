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

namespace SimpleThings\Tests\EntityAudit;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\Tests\TestUtil;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Shared connection when a TestCase is run alone (outside of its functional suite).
     *
     * @var Connection|null
     */
    protected static $_sharedConn;

    /**
     * @var EntityManager
     */
    protected $_em;

    /**
     * @var SchemaTool
     */
    protected $_schemaTool;

    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $_sqlLoggerStack;

    /**
     * The names of the model sets used in this testcase.
     *
     * @var array
     */
    protected $_usedModelSets = array();

    /**
     * Whether the database schema has already been created.
     *
     * @var array
     */
    protected static $_tablesCreated = array();

    /**
     * Array of entity class name to their tables that were created.
     *
     * @var array
     */
    protected static $_entityTablesCreated = array();

    /**
     * @var AuditManager
     */
    protected $_auditManager = null;

    protected $schemaEntities = array();

    protected $auditedEntities = array();

    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     *
     * @return void
     */
    protected function setUp()
    {
        if (!isset(static::$_sharedConn)) {
            static::$_sharedConn = TestUtil::getConnection();
        }

        if (!$this->_em) {
            $this->_em = $this->_getEntityManager();
            $this->_schemaTool = new SchemaTool($this->_em);
        }

        if (!$this->_auditManager) {
            $this->_auditManager = $this->_getAuditManager();
        }

        $em = $this->_em;
        $this->_schemaTool->createSchema(array_map(function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities));

        $this->_sqlLoggerStack->enabled = true;
    }

    /**
     * Gets an EntityManager for testing purposes.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function _getEntityManager() {

        $this->_sqlLoggerStack = new DebugStack();
        $this->_sqlLoggerStack->enabled = false;

        $config = new Configuration();
        $config->setMetadataCacheImpl(new ArrayCache());
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(
            realpath(__DIR__ . '/Fixtures/Core'),
            realpath(__DIR__ . '/Fixtures/Issue'),
            realpath(__DIR__ . '/Fixtures/Relation')
        ), false));

        $conn = static::$_sharedConn;
        $conn->getConfiguration()->setSQLLogger($this->_sqlLoggerStack);

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
    protected function _getAuditManager()
    {
        $auditConfig = new AuditConfiguration();
        $auditConfig->setCurrentUsername('beberlei');
        $auditConfig->setAuditedEntityClasses($this->auditedEntities);
        $auditConfig->setGlobalIgnoreColumns(array('ignoreme'));

        $auditManager = new AuditManager($auditConfig);
        $auditManager->registerEvents(static::$_sharedConn->getEventManager());

        return $auditManager;
    }

    public function tearDown()
    {
        $schemaTool = new SchemaTool($this->_em);
        $em = $this->_em;

        try {
            $schemaTool->dropSchema(array_map(function ($value) use ($em) {
                    return $em->getClassMetadata($value);
                }, $this->schemaEntities));
        } catch (\Exception $e) {
            if ($GLOBALS['DOCTRINE_DRIVER'] != 'pdo_mysql' ||
                !($e instanceof \PDOException && strpos($e->getMessage(), 'Base table or view already exists') !== false)
            ) {
                throw $e;
            }
        }
    }
}
