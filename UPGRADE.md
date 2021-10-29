UPGRADE 1.x
===========

UPGRADE FROM 1.x to 1.x
=======================

### `SimpleThings\EntityAudit\EventListener\CreateSchemaListener`

"postGenerateSchema" event is not listen anymore. The table responsible for storing
the revisions index is created on the "postGenerateSchemaTable" event.
A foreign key constraint was added for the relation between the revisions index and
the audit tables, disallowing to delete the records in the index if their referenced
values exist in the audit tables.
