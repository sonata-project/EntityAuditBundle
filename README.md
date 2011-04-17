# EntityAudit Extension for Doctrine2

This extension for Doctrine 2 is inspired by [Hibernate Envers](http://www.jboss.org/envers) and
allows full versioning of entities and their associations.

## How does it work?

There are a bunch of different approaches to auditing or versioning of database tables. This extension
creates a mirroring table for each audited entitys table that is suffixed with "_audit". Besides all the columns
of the audited entity there are two additional fields:

* rev - Contains the global revision number generated from a "revisions" table.
* revtype - Contains one of 'INS', 'UPD' or 'DEL' as an information to which type of database operation caused this revision log entry.

The global revision table contains an id, timestamp, username and change comment field.

With this approach it is possible to version an application with its changes to associations at the particular
points in time. All the querying logic is not implemented in the extension yet, only the writing
changes into versioning table works.

This extension hooks into the SchemaTool generation process so that it will automatically
create the necessary DDL statements for your audited entities.

## Installation (In Symfony2 Application)

1. Register Bundle in AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            //...
            new SimpleThings\EntityAudit\SimpleThingsEntityAuditBundle(),
            //...
        );
        return $bundles;
    }

2. Load extension "simple_things_entity_audit" and specify the audited entities (yes, that ugly for now!)

    simple_things_entity_audit:
        audited_entities:
            - MyBundle\Entity\MyEntity
            - MyBundle\Entity\MyEntity2

3. Call ./app/console doctrine:schema:update --dump-sql to see the new tables in the update schema queue.

## Installation (Standalone)

For standalone usage you have to pass the entity class names to be audited to the MetadataFactory
instance and configure the two event listeners.

    use Doctrine\ORM\EntityManager;
    use Doctrine\Common\EventManager;
    use SimpleThings\EntityAudit\EventListener\CreateSchemaListener;
    use SimpleThings\EntityAudit\EventListener\LogRevisionsListener;
    use SimpleThings\EntityAudit\AuditConfiguration;
    use SimpleThings\EntityAudit\AuditManager;

    $auditconfig = new AuditConfiguration();
    $auditconfig->setAuditedEntityClasses(array(
        'SimpleThings\EntityAudit\Tests\ArticleAudit', 'SimpleThings\EntityAudit\Tests\UserAudit'
    ));
    $evm = new EventManager();
    $auditManager = new AuditManager($auditconfig);
    $auditManager->registerEvents($evm);

    $em = EntityManager::create($conn, $config, $evm);

## TODOS

* Implement Querying version history for a given entity + id
* Implement Querying for actual instances of the old entities.
* Currently only works with auto-increment databases
* Proper metadata mapping is necessary
* It does NOT work with Joined-Table-Inheritance (Single Table Inheritance should work, but not tested)
* Make global revisions table configurable (for example with user id or comment of what was changed).
* Implement versioning of Many-To-Many assocations