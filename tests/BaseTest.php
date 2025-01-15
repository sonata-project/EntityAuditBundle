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

namespace Sonata\EntityAuditBundle\Tests;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;

abstract class BaseTest extends TestCase
{
    /**
     * @var Connection|null
     */
    protected static $conn;

    /**
     * NEXT_MAJOR: Change typehint to EntityManagerInterface.
     */
    protected ?EntityManager $em = null;

    protected ?AuditManager $auditManager = null;

    /**
     * @var string[]
     *
     * @phpstan-var list<class-string>
     */
    protected $schemaEntities = [];

    /**
     * @var string[]
     *
     * @phpstan-var list<class-string>
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

        $mappingPaths = [
            __DIR__.'/Fixtures/Core',
            __DIR__.'/Fixtures/Issue',
            __DIR__.'/Fixtures/Relation',
        ];

        if (version_compare(\PHP_VERSION, '8.1.0', '>=')) {
            $mappingPaths[] = __DIR__.'/Fixtures/PHP81Issue';
        }

        $config = ORMSetup::createAttributeMetadataConfiguration($mappingPaths, true);
        $config->setSchemaManagerFactory(new DefaultSchemaManagerFactory());
        $connection = $this->_getConnection($config);

        $this->em = new EntityManager($connection, $config, new EventManager());

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

    protected function _getConnection(Configuration $config): Connection
    {
        if (!isset(self::$conn)) {
            $url = getenv('DATABASE_URL');
            if (false !== $url) {
                $params = ['url' => $url];
            } else {
                $params = [
                    'driver' => 'pdo_sqlite',
                    'memory' => true,
                ];
            }

            self::$conn = DriverManager::getConnection($params, $config);
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

        $this->auditManager = new AuditManager($auditConfig, $this->getClock());
        $this->auditManager->registerEvents($this->getEntityManager()->getEventManager());

        return $this->auditManager;
    }

    protected function getClock(): ?ClockInterface
    {
        return null;
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
