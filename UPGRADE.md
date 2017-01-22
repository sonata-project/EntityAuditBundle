
# Upgrade to unreleased

## BC BREAK: Current user name resolution

Previously the username that was recorded against revisions was resolved by `SimpleThings\EntityAudit\Request\CurrentUserListener` (``simplethings_entityaudit.request.current_user_listener` service).

This has been removed and replaced with `SimpleThings\EntityAudit\User\TokenStorageUsernameCallable`.

The bundle configuration has changed to reflect these changes:

Before:
```yml
simple_things_entity_audit:
    listener:
        current_username: true
```

After:
```yml
simple_things_entity_audit:
    service:
        username_callable: simplethings_entityaudit.username_callable.token_storage
```

The above after configuration is the default and does not need setting explicitly.

## BC BREAK:
Following methods has been removed
```php
AuditReader::setLoadAuditedCollections($loadAuditedCollections)
AuditReader::setLoadAuditedEntities($loadAuditedEntities)
AuditReader::setLoadNativeCollections($loadNativeCollections)
AuditReader::setLoadNativeEntities($loadNativeEntities)
```
and are replaced by $options arguments at AuditReader::__c'tor or AuditReader::find.
```php
$auditReader->find(Foo::class, 1, 1, [
    AuditReader::LOAD_AUDITED_COLLECTIONS => false,
    AuditReader::LOAD_AUDITED_ENTITIES => false,
    AuditReader::LOAD_NATIVE_COLLECTIONS => false,
    AuditReader::LOAD_NATIVE_ENTITIES => false,
]);
```
