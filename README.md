# EntityAuditBundle

This extension for Doctrine 2 is inspired by [Hibernate Envers](http://www.jboss.org/envers) and
allows full versioning of entities and their associations.

[![Latest Stable Version](https://poser.pugx.org/sonata-project/entity-audit-bundle/v/stable)](https://packagist.org/packages/sonata-project/entity-audit-bundle)
[![Latest Unstable Version](https://poser.pugx.org/sonata-project/entity-audit-bundle/v/unstable)](https://packagist.org/packages/sonata-project/entity-audit-bundle)
[![License](https://poser.pugx.org/sonata-project/entity-audit-bundle/license)](https://packagist.org/packages/sonata-project/entity-audit-bundle)

[![Total Downloads](https://poser.pugx.org/sonata-project/entity-audit-bundle/downloads)](https://packagist.org/packages/sonata-project/entity-audit-bundle)
[![Monthly Downloads](https://poser.pugx.org/sonata-project/entity-audit-bundle/d/monthly)](https://packagist.org/packages/sonata-project/entity-audit-bundle)
[![Daily Downloads](https://poser.pugx.org/sonata-project/entity-audit-bundle/d/daily)](https://packagist.org/packages/sonata-project/entity-audit-bundle)

Branch | Github Actions | Code Coverage |
------ | -------------- | ------------- |
1.x    | [![Test][test_stable_badge]][test_stable_link]     | [![Coverage Status][coverage_stable_badge]][coverage_stable_link]     |
2.x.   | [![Test][test_unstable_badge]][test_unstable_link] | [![Coverage Status][coverage_unstable_badge]][coverage_unstable_link] |

## Support

For general support and questions, please use [StackOverflow](http://stackoverflow.com/questions/tagged/sonata).

If you think you found a bug or you have a feature idea to propose, feel free to open an issue
**after looking** at the [contributing guide](CONTRIBUTING.md).

## License

This package is available under the [LGPL license](LICENSE).

[test_stable_badge]: https://github.com/sonata-project/EntityAuditBundle/workflows/Test/badge.svg?branch=1.x
[test_stable_link]: https://github.com/sonata-project/EntityAuditBundle/actions?query=workflow:test+branch:1.x
[test_unstable_badge]: https://github.com/sonata-project/EntityAuditBundle/workflows/Test/badge.svg?branch=2.x
[test_unstable_link]: https://github.com/sonata-project/EntityAuditBundle/actions?query=workflow:test+branch:2.x

[coverage_stable_badge]: https://codecov.io/gh/sonata-project/EntityAuditBundle/branch/1.x/graph/badge.svg
[coverage_stable_link]: https://codecov.io/gh/sonata-project/EntityAuditBundle/branch/1.x
[coverage_unstable_badge]: https://codecov.io/gh/sonata-project/EntityAuditBundle/branch/2.x/graph/badge.svg
[coverage_unstable_link]: https://codecov.io/gh/sonata-project/EntityAuditBundle/branch/2.x

## How does it work?

There are a bunch of different approaches to auditing or versioning of database tables. This extension
creates a mirroring table for each audited entitys table that is suffixed with "_audit". Besides all the columns
of the audited entity there are two additional fields:

* rev - Contains the global revision number generated from a "revisions" table.
* revtype - Contains one of 'INS', 'UPD' or 'DEL' as an information to which type of database operation caused this revision log entry.

The global revision table contains an id, timestamp, username and change comment field.

With this approach it is possible to version an application with its changes to associations at the particular
points in time.

This extension hooks into the SchemaTool generation process so that it will automatically
create the necessary DDL statements for your audited entities.

## Installation

### Installing the bundle

Simply run assuming you have composer:

```bash
$ composer require sonata-project/entity-audit-bundle
```

### Enable the bundle

Finally, enable the bundle in the kernel:

```php
// config/bundles.php

return [
    //...
    SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle::class => ['all' => true],
    //...
];
```

### Configuration

Load extension "simple_things_entity_audit" and specify the audited entities

```yaml
# config/packages/entity_audit.yaml

simple_things_entity_audit:
    audited_entities:
        - MyBundle\Entity\MyEntity
        - MyBundle\Entity\MyEntity2
```
If you need to exclude some entity properties from triggering a revision use:

```yaml
# config/packages/entity_audit.yaml

simple_things_entity_audit:
    global_ignore_columns:
        - created_at
        - updated_at
```

In order to work with other connection or entity manager than "default", use these settings:
```yaml
# config/packages/entity_audit.yaml

simple_things_entity_audit:
    connection: custom
    entity_manager: custom
```

### Creating new tables

Call the command below to see the new tables in the update schema queue.

```bash
./bin/console doctrine:schema:update --dump-sql
```

## Installation (Standalone)

For standalone usage you have to pass the entity class names to be audited to the MetadataFactory
instance and configure the two event listeners.

```php
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\EventManager;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Tests\ArticleAudit;
use SimpleThings\EntityAudit\Tests\UserAudit;

$auditConfig = new AuditConfiguration();
$auditConfig->setAuditedEntityClasses([ArticleAudit::class, UserAudit::class]);
$auditConfig->setGlobalIgnoreColumns(['created_at', 'updated_at']);

$eventManager = new EventManager();
$auditManager = new AuditManager($auditConfig);
$auditManager->registerEvents($eventManager);

$config = new Configuration();
// $config ...
$connection = [];
$entityManager = EntityManager::create($connection, $config, $eventManager);
```

## Usage

Querying the auditing information is done using a `SimpleThings\EntityAudit\AuditReader` instance.

```php
use SimpleThings\EntityAudit\AuditReader;

class DefaultController extends Controller
{
    public function indexAction(AuditReader $auditReader)
    {
    }
}
```

In a standalone application you can create the audit reader from the audit manager:

```php
$auditReader = $auditManager->createAuditReader($entityManager);
```

### Find entity state at a particular revision

This command also returns the state of the entity at the given revision, even if the last change
to that entity was made in a revision before the given one:

```php
$articleAudit = $auditReader->find(
    SimpleThings\EntityAudit\Tests\ArticleAudit::class,
    $id = 1,
    $rev = 10
);
```

Instances created through `AuditReader#find()` are *NOT* injected into the EntityManagers UnitOfWork,
they need to be merged into the EntityManager if it should be reattached to the persistence context
in that old version.

### Find Revision History of an audited entity

```php
$revisions = $auditReader->findRevisions(
    SimpleThings\EntityAudit\Tests\ArticleAudit::class,
    $id = 1
);
```

A revision has the following API:

```php
class Revision
{
    public function getRev();
    public function getTimestamp();
    public function getUsername();
}
```

### Find Changed Entities at a specific revision

```php
$changedEntities = $auditReader->findEntitiesChangedAtRevision(10);
```

A changed entity has the API:

```php
class ChangedEntity
{
    public function getClassName();
    public function getId();
    public function getRevisionType();
    public function getEntity();
}
```

### Find Current Revision of an audited Entity

```php
$revision = $auditReader->getCurrentRevision(
    'SimpleThings\EntityAudit\Tests\ArticleAudit',
    $id = 3
);
```

## Setting the Current Username

Each revision automatically saves the username that changes it. For this to work, the username must be resolved.

In the Symfony web context the username is resolved from the one in the current security context token.

You can override this with your own behaviour by configuring the `username_callable` service in the bundle configuration.
Your custom service must be a `callable` and should return a `string` or `null`.

```yaml
# config/packages/entity_audit.yaml

simple_things_entity_audit:
    service:
        username_callable: acme.username_callable
```

In a standalone app or Symfony command you can set an username callable to a specific value using the `AuditConfiguration`.

```php
$auditConfig = new \SimpleThings\EntityAudit\AuditConfiguration();
$auditConfig->setUsernameCallable(function () {
	$username = //your customer logic
    return username;
});
```

## Viewing auditing

A default Symfony controller is provided that gives basic viewing capabilities of audited data.

To use the controller, import the routing **(don't forget to secure the prefix you set so that
only appropriate users can get access)**

```yaml
# config/routes.yaml

simple_things_entity_audit:
    resource: "@SimpleThingsEntityAuditBundle/Resources/config/routing/audit.xml"
    prefix: /audit
```

This provides you with a few different routes:

 * ```simple_things_entity_audit_home``` - Displays a paginated list of revisions, their timestamps and the user who performed the revision
 * ```simple_things_entity_audit_viewrevision``` - Displays the classes that were modified in a specific revision
 * ```simple_things_entity_audit_viewentity``` - Displays the revisions where the specified entity was modified
 * ```simple_things_entity_audit_viewentity_detail``` - Displays the data for the specified entity at the specified revision
 * ```simple_things_entity_audit_compare``` - Allows you to compare the changes of an entity between 2 revisions

## TODOS

* Currently only works with auto-increment databases
* Proper metadata mapping is necessary, allow to disable versioning for fields and associations.
* It does NOT work with Joined-Table-Inheritance (Single Table Inheritance should work, but not tested)
