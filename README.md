# EntityAudit Extension for Doctrine2

| Master | 1.0 Branch |
|:----------------:|:----------:|
| [![Build Status](https://travis-ci.org/simplethings/EntityAuditBundle.svg?branch=master)](https://travis-ci.org/simplethings/EntityAuditBundle) | [![Build Status](https://travis-ci.org/simplethings/EntityAuditBundle.svg?branch=1.0)](https://travis-ci.org/simplethings/EntityAuditBundle) |
|[documentation](https://github.com/simplethings/EntityAuditBundle/blob/master/README.md)|[documentation](https://github.com/simplethings/EntityAudit/blob/1.0/README.md)

**WARNING: Master isn't stable yet and it might not be working! Please use version `^1.0` and this documentation: https://github.com/simplethings/EntityAudit/blob/1.0/README.md**

This extension for Doctrine 2 is inspired by [Hibernate Envers](http://www.jboss.org/envers) and
allows full versioning of entities and their associations.

## Is this library still maintained?

[Maybe?](https://github.com/simplethings/EntityAudit/issues/203) - please discuss and support us in the linked issue

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

## Installation (Standalone)

### Installing the lib/bundle

Simply run assuming you have installed composer.phar or composer binary:

``` bash
$ composer require simplethings/entity-audit-bundle
```

For standalone usage you have to pass the EntityManager.

```php
use Doctrine\ORM\EntityManager;
use SimpleThings\EntityAudit\AuditManager;

$config = new \Doctrine\ORM\Configuration();
// $config ...
$conn = array();
$em = EntityManager::create($conn, $config, $evm);

$auditManager = AuditManager::create($em);
```

## Installation (In Symfony2 Application)

### Enable the bundle

Enable the bundle in the kernel:

``` php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        //...
        new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
        //...
    );
    return $bundles;
}
```

### Configuration

You can configure the audited tables. 

##### app/config/config.yml
```yml
simple_things_entity_audit:
    entity_manager: default
    table_prefix: ''
    table_suffix: _audit
    revision_field_name: rev
    revision_type_field_name: revtype
    revision_table_name: revisions
    revision_id_field_type: integer
```

### Creating new tables

Call the command below to see the new tables in the update schema queue.

```bash
./app/console doctrine:schema:update --dump-sql 
```

## Usage

### Define auditable entities
 
You need add `Auditable` annotation for the entities which you want to auditable.
  
```php
use Doctrine\ORM\Mapping as ORM;
use SimpleThings\EntityAudit\Mapping\Annotation as Audit;

/**
 * @ORM\Entity()
 * @Audit\Auditable()
 */
class Page {
 //...
}
```

You can also ignore fields in an specific entity.
 
```php
class Page {

    /**
     * @ORM\Column(type="string")
     * @Audit\Ignore()
     */
    private $ignoreMe;

}
``` 

### Use AuditReader

Querying the auditing information is done using a `SimpleThings\EntityAudit\AuditReader` instance.

In a standalone application you can create the audit reader from the audit manager:

```php
$auditReader = $auditManager->createAuditReader();
```

In Symfony2 the AuditReader is registered as the service "simplethings_entityaudit.reader":

```php
class DefaultController extends Controller
{
    public function indexAction()
    {
        $auditReader = $this->container->get('simplethings_entityaudit.manager')->createAuditReader();
    }
}
```

### Find entity state at a particular revision

This command also returns the state of the entity at the given revision, even if the last change
to that entity was made in a revision before the given one:

```php
$articleAudit = $auditReader->find(
    'SimpleThings\EntityAudit\Tests\ArticleAudit',
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
    'SimpleThings\EntityAudit\Tests\ArticleAudit',
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

In the Symfony2 web context the username is resolved from the one in the current security context token.

You can override this with your own behaviour by configuring the `username_callable` service in the bundle configuration. Your custom service must be a `callable` and should return a `string` or `null`.

##### app/config/config.yml
```yml
simple_things_entity_audit:
    service:
        username_callable: acme.username_callable
```

In a standalone app or Symfony command you can username callable to a specific value using the `AuditConfiguration`.

```php
$auditConfig = new \SimpleThings\EntityAudit\AuditConfiguration();
$auditConfig->setUsernameCallable(function () {
	$username = //your customer logic
    return username;
});
```

## Viewing auditing

A default Symfony2 controller is provided that gives basic viewing capabilities of audited data.

To use the controller, import the routing **(don't forget to secure the prefix you set so that
only appropriate users can get access)**

##### app/config/routing.yml
```yml
simple_things_entity_audit:
    resource: "@SimpleThingsEntityAuditBundle/Resources/config/routing.yml"
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

## Supported DB

* MySQL / MariaDB
* PostgesSQL
* SQLite

*We can only really support the databases if we can test them via Travis.*

## Contributing

Please before commiting, run this command `./vendor/bin/php-cs-fixer fix --verbose` to normalize the coding style.

If you already have the fixer locally you can run `php-cs-fixer fix .`.
