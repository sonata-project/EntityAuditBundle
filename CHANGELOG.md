# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.3.0...1.3.1) - 2021-04-09
### Fixed
- [[#386](https://github.com/sonata-project/EntityAuditBundle/pull/386)] `AuditReader::findRevisionHistory()` phpdoc ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.3.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.2.0...1.3.0) - 2021-04-08
### Added
- [[#382](https://github.com/sonata-project/EntityAuditBundle/pull/382)] Allow `$revisions` param to be a string in `AuditManager` methods. ([@VincentLanglet](https://github.com/VincentLanglet))

### Deprecated
- [[#382](https://github.com/sonata-project/EntityAuditBundle/pull/382)] Passing another value than 'string', 'integer', 'smallint', 'bigint' or 'guid' for the `revision_id_field_type` value. ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.2.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.1.1...1.2.0) - 2021-03-24
### Added
- [[#375](https://github.com/sonata-project/EntityAuditBundle/pull/375)] Added phpstan annotation for `AuditReader::find()` method ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.1.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.1.0...1.1.1) - 2021-03-21
### Fixed
- [[#373](https://github.com/sonata-project/EntityAuditBundle/pull/373)] Improved `AuditReader` phpdoc. ([@VincentLanglet](https://github.com/VincentLanglet))

## [v1.1.0](https://github.com/sonata-project/EntityAuditBundle/compare/v1.0.9...v1.1.0) - 2021-02-24
### Added
- [[#365](https://github.com/sonata-project/EntityAuditBundle/pull/365)] Added routing in xml: `src\Resources\config\routing\audit.xml` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Added `SimpleThings\EntityAudit\Action\CompareAction` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Added `SimpleThings\EntityAudit\Action\IndexAction` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Added `SimpleThings\EntityAudit\Action\ViewDetailAction` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Added `SimpleThings\EntityAudit\Action\ViewEntityAction` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Added `SimpleThings\EntityAudit\Action\ViewRevisionAction` ([@wbloszyk](https://github.com/wbloszyk))
- [[#350](https://github.com/sonata-project/EntityAuditBundle/pull/350)] PHP 8 support ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#355](https://github.com/sonata-project/EntityAuditBundle/pull/355)] `connection` configuration node in order to use a different connection than "default" ([@phansys](https://github.com/phansys))
- [[#355](https://github.com/sonata-project/EntityAuditBundle/pull/355)] `entity_manager` configuration node in order to use a different entity manager than "default" ([@phansys](https://github.com/phansys))
- [[#352](https://github.com/sonata-project/EntityAuditBundle/pull/352)] "symfony/config" dependency ([@phansys](https://github.com/phansys))

### Changed
- [[#366](https://github.com/sonata-project/EntityAuditBundle/pull/366)] Change `xml` configuration in favor of `php` ([@wbloszyk](https://github.com/wbloszyk))
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Changed controllers for routing from `AuditController` in favor for `Actions` ([@wbloszyk](https://github.com/wbloszyk))

### Deprecated
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Deprecated `SimpleThings\EntityAudit\Controller\AuditController` ([@wbloszyk](https://github.com/wbloszyk))

### Fixed
- [[#364](https://github.com/sonata-project/EntityAuditBundle/pull/364)] Fixed page working with `symfony/framework-bundle` >= 5.0 ([@wbloszyk](https://github.com/wbloszyk))
- [[#324](https://github.com/sonata-project/EntityAuditBundle/pull/324)] `AuditedCollection` methods in order to respect `Collection` interface ([@phansys](https://github.com/phansys))

### v1.0.5 (2017/05/26)
- [#281](https://github.com/simplethings/EntityAuditBundle/pull/281) Removal of hardcoded revision field name (@c0ntax)

### v1.0.4 (2017/04/19)
- [#279](https://github.com/simplethings/EntityAuditBundle/issues/279) Fix versioning

### v1.0.3 (2017/04/19)
- [#275](https://github.com/simplethings/EntityAuditBundle/pull/275) Fix auditing of entities with fields that require php conversion (@TheRatG)

### v1.0.2 (2017/01/30)
- [#258](https://github.com/simplethings/EntityAuditBundle/issues/258) global_ignore_columns doesn't work

### v1.0.1 (2017/01/13)
- [#250](https://github.com/simplethings/EntityAudit/pull/250) Fix OneToOne reverse relation Doctrine fallback query (@Soullivaneuh)
- [#227](https://github.com/simplethings/EntityAudit/pull/227) Fallback to native entity if no revision found for properties (@Soullivaneuh)

### v1.0.0 (2017/01/06)
- [#218](https://github.com/simplethings/EntityAudit/pull/218) Failing one to one bidirectional fix (@peschee)
- [#231](https://github.com/simplethings/EntityAudit/pull/231) Enable join column to be an id (@oconnedk)
- [#159](https://github.com/simplethings/EntityAudit/pull/159) [ADD] PHP-CS-Fixer (@Th3Mouk)
- [#197](https://github.com/simplethings/EntityAudit/pull/197) Fix auditing of entities with fields that require sql conversion (@jamescdavis)
- [#210](https://github.com/simplethings/EntityAudit/pull/210) Provide a way to customize the revision username. (@bendavies)

### v0.9.2 (2016-08-22)
- [#209](https://github.com/simplethings/EntityAudit/pull/209) run tests against postgresql (@bendavies)
- [#208](https://github.com/simplethings/EntityAudit/pull/208) run tests againts mysql (@bendavies)
- [#206](https://github.com/simplethings/EntityAudit/pull/206) clean up base test. (@bendavies)
- [#198](https://github.com/simplethings/EntityAudit/pull/198) fix: use the type of association fields in LogRevisionsListener (@v-technologies)
- [#205](https://github.com/simplethings/EntityAudit/pull/205) Travis improvements (@bendavies)
- [#204](https://github.com/simplethings/EntityAudit/pull/204) conform to psr-4 (@bendavies)
- [#181](https://github.com/simplethings/EntityAudit/pull/181) Update CreateSchemaListener.php (@TheRatG)

### 0.9.1 (2016-03-03)

* added support for symfony/framework-bundle 3.x
* added support for PHP 7.x

#### breaking changes

* dropped support for symfony/framework-bundle < 2.7


### 0.9.0 (2016-01-06)

* added support for doctrine/orm 2.5.x
* some CS fixes
* moved test case classes in their own files

#### breaking changes

* removed support for doctrine/orm  < 2.4
* removed support for doctrine/doctrine-bundle  < 1.4
* removed support for gedmo/doctrine-extensions < 2.3.1
* removed support for symfony/framework-bundle < 2.3
