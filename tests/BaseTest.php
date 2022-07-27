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

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\DoctrineExtensions;
use PHPUnit\Framework\TestCase;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

abstract class BaseTest extends TestCase
{
    /**
     * @var Connection|null
     */
    protected static $conn;

    /**
     * NEXT_MAJOR: Use `\Doctrine\ORM\EntityManagerInterface` instead.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AuditManager
     */
    protected $auditManager;

    /**
     * @var string[]
     *
     * @phpstan-var class-string[]
     */
    protected $schemaEntities = [];

    /**
     * @var string[]
     *
     * @phpstan-var class-string[]
     */
    protected $auditedEntities = [];

    /**
     * @var string[]
     *
     * @phpstan-var array<string, class-string<Type>>
     */
    protected $customTypes = [];

    private ?SchemaTool $schemaTool = null;

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
        $config->setQueryCache(new ArrayAdapter());
        $config->setProxyDir(__DIR__.'/Proxies');
        $config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_EVAL);
        $config->setProxyNamespace('SimpleThings\EntityAudit\Tests\Proxies');

        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([
            realpath(__DIR__.'/Fixtures/Core'),
            realpath(__DIR__.'/Fixtures/Issue'),
            realpath(__DIR__.'/Fixtures/Relation'),
        ], false));

        DoctrineExtensions::registerAnnotations();

        $connection = $this->_getConnection();

        // get rid of more global state
        $evm = $connection->getEventManager();
        foreach ($evm->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $evm->removeEventListener([$event], $listener);
            }
        }

        $this->em = EntityManager::create($connection, $config);

        foreach ($this->customTypes as $customTypeName => $customTypeClass) {
            if (!Type::hasType($customTypeName)) {
                Type::addType($customTypeName, $customTypeClass);
            }
            $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('db_'.$customTypeName, $customTypeName);
        }

        return $this->em;
    }

    protected function getSchemaTool(): SchemaTool
    {
        if (null !== $this->schemaTool) {
            return $this->schemaTool;
        }

        $this->schemaTool = new SchemaTool($this->getEntityManager());

        return $this->schemaTool;
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
        $auditConfig->setGlobalIgnoreColumns(['ignoreme']);
        $auditConfig->setUsernameCallable(static fn (): string => 'beberlei');

        $auditManager = new AuditManager($auditConfig);
        $auditManager->registerEvents($this->_getConnection()->getEventManager());

        return $this->auditManager = $auditManager;
    }

    protected function setUpEntitySchema(): void
    {
        $em = $this->getEntityManager();
        $classes = array_map(
            static fn (string $value): ClassMetadata => $em->getClassMetadata($value),
            $this->schemaEntities
        );

        $this->getSchemaTool()->createSchema($classes);
    }

    protected function tearDownEntitySchema(): void
    {
        $em = $this->getEntityManager();
        $classes = array_map(
            static fn (string $value): ClassMetadata => $em->getClassMetadata($value),
            $this->schemaEntities
        );

        $this->getSchemaTool()->dropSchema($classes);
    }
}
