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
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\EntityCache;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection|null
     */
    protected static $conn;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    /**
     * @var AuditManager
     */
    protected $auditManager;

    protected $schemaEntities = array();

    protected $auditedEntities = array();

    public function setUp()
    {
        $this->getEntityManager();
        $this->getSchemaTool();
        $this->getAuditManager();
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
        if (null !== $this->em) {
            return $this->em;
        }

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

        $connection = $this->_getConnection();

        // get rid of more global state
        $evm = $connection->getEventManager();
        foreach ($evm->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evm->removeEventListener(array($event), $listener);
            }
        }

        $this->em = EntityManager::create($connection, $config);

        if (isset($this->customTypes) and is_array($this->customTypes)) {
            foreach ($this->customTypes as $customTypeName => $customTypeClass) {
                if (!Type::hasType($customTypeName)) {
                    Type::addType($customTypeName, $customTypeClass);
                }
                $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('db_' . $customTypeName, $customTypeName);
            }
        }

        return $this->em;
    }

    /**
     * @return SchemaTool
     */
    protected function getSchemaTool()
    {
        if (null !== $this->schemaTool) {
            return $this->schemaTool;
        }

        return $this->schemaTool = new SchemaTool($this->getEntityManager());
    }

    /**
     * @return Connection
     */
    protected function _getConnection()
    {
        if (!isset(self::$conn)) {
            if(isset(
                $GLOBALS['db_type'],
                $GLOBALS['db_username'],
                $GLOBALS['db_password'],
                $GLOBALS['db_host'],
                $GLOBALS['db_name'],
                $GLOBALS['db_port']
            )){
                $params = array(
                    'driver' => $GLOBALS['db_type'],
                    'user' => $GLOBALS['db_username'],
                    'password' => $GLOBALS['db_password'],
                    'host' => $GLOBALS['db_host'],
                    'dbname' => $GLOBALS['db_name'],
                    'port' => $GLOBALS['db_port'],
                );

                $tmpParams = $params;
                $dbname = $params['dbname'];
                unset($tmpParams['dbname']);

                $conn = DriverManager::getConnection($tmpParams);
                $platform = $conn->getDatabasePlatform();

                if ($platform->supportsCreateDropDatabase()) {
                    $conn->getSchemaManager()->dropAndCreateDatabase($dbname);
                } else {
                    $sm = $conn->getSchemaManager();
                    $schema = $sm->createSchema();
                    $stmts = $schema->toDropSql($conn->getDatabasePlatform());
                    foreach ($stmts as $stmt) {
                        $conn->exec($stmt);
                    }
                }

                $conn->close();

            } else {
                $params = array(
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                );
            }

            self::$conn = DriverManager::getConnection($params);
        }

        return self::$conn;
    }

    /**
     * @return AuditManager
     */
    protected function getAuditManager()
    {
        if (null !== $this->auditManager) {
            return $this->auditManager;
        }

        $auditConfig = AuditConfiguration::forEntities($this->auditedEntities);
        $auditConfig->setGlobalIgnoreColumns(array('ignoreme'));
        $auditConfig->setUsernameCallable(function () {
            return 'beberlei';
        });

        $auditManager = new AuditManager($auditConfig, new EntityCache());
        $auditManager->registerEvents($this->_getConnection()->getEventManager());

        return $this->auditManager = $auditManager;
    }

    protected function setUpEntitySchema()
    {
        $em = $this->getEntityManager();
        $classes = array_map(function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->getSchemaTool()->createSchema($classes);
    }

    protected function tearDownEntitySchema()
    {
        $em = $this->getEntityManager();
        $classes = array_map(function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->getSchemaTool()->dropSchema($classes);
    }
}
