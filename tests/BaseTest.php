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

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo;
use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Tests\Fixtures\Core\ProfileAudit;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

abstract class BaseTest extends TestCase
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
     * @var AuditManager
     */
    protected $auditManager;

    protected $schemaEntities = [];

    protected $auditedEntities = [];

    /**
     * @var SchemaTool
     */
    private $schemaTool;

    protected function setUp(): void
    {
        $this->getEntityManager();
        $this->getSchemaTool();
        $this->getAuditManager();
        $this->setUpEntitySchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownEntitySchema();
    }

    protected function getEntityManager(): EntityManager
    {
        if (null !== $this->em) {
            return $this->em;
        }

        $config = new Configuration();
        $config->setMetadataCache(new ArrayAdapter());
        $config->setQueryCacheImpl(DoctrineProvider::wrap(new ArrayAdapter()));
        $config->setProxyDir(__DIR__.'/Proxies');
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setProxyNamespace('SimpleThings\EntityAudit\Tests\Proxies');

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([
            realpath(__DIR__.'/Fixtures/Core'),
            realpath(__DIR__.'/Fixtures/Issue'),
            realpath(__DIR__.'/Fixtures/Relation'),
        ], false));

        Gedmo\DoctrineExtensions::registerAnnotations();

        $connection = $this->_getConnection();

        // get rid of more global state
        $evm = $connection->getEventManager();
        foreach ($evm->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evm->removeEventListener([$event], $listener);
            }
        }

        $this->em = EntityManager::create($connection, $config);

        if (isset($this->customTypes) && \is_array($this->customTypes)) {
            foreach ($this->customTypes as $customTypeName => $customTypeClass) {
                if (!Type::hasType($customTypeName)) {
                    Type::addType($customTypeName, $customTypeClass);
                }
                $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('db_'.$customTypeName, $customTypeName);
            }
        }

        return $this->em;
    }

    protected function getSchemaTool(): SchemaTool
    {
        if (null !== $this->schemaTool) {
            return $this->schemaTool;
        }

        return $this->schemaTool = new SchemaTool($this->getEntityManager());
    }

    protected function _getConnection(): Connection
    {
        if (!isset(self::$conn)) {
            if (false !== getenv('DATABASE_URL')) {
                $params = ['url' => getenv('DATABASE_URL')];
            } else {
                $params = [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ];
            }

            self::$conn = DriverManager::getConnection($params);
        }

        return self::$conn;
    }

    protected function getAuditManager(): AuditManager
    {
        if (null !== $this->auditManager) {
            return $this->auditManager;
        }

        $auditConfig = AuditConfiguration::forEntities($this->auditedEntities);
        $auditConfig->setConvertEnumToString(true);
        $auditConfig->setDatabasePlatform($this->getEntityManager()->getConnection()->getDatabasePlatform());
        $auditConfig->setGlobalIgnoreColumns(['ignoreMe']);
        $auditConfig->setEntityIgnoredProperties([ProfileAudit::class => ['ignoreProperty']]);
        $auditConfig->setUsernameCallable(static function () {
            return 'beberlei';
        });

        $auditManager = new AuditManager($auditConfig);
        $auditManager->registerEvents($this->_getConnection()->getEventManager());

        return $this->auditManager = $auditManager;
    }

    protected function setUpEntitySchema(): void
    {
        $em = $this->getEntityManager();
        $classes = array_map(static function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->getSchemaTool()->createSchema($classes);
    }

    protected function tearDownEntitySchema(): void
    {
        $em = $this->getEntityManager();
        $classes = array_map(static function ($value) use ($em) {
            return $em->getClassMetadata($value);
        }, $this->schemaEntities);

        $this->getSchemaTool()->dropSchema($classes);
    }
}
