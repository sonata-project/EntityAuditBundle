# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.18.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.17.1...1.18.0) - 2024-07-14
### Added
- [[#590](https://github.com/sonata-project/EntityAuditBundle/pull/590)] Support for `doctrine/orm` 3 ([@franmomu](https://github.com/franmomu))

### Fixed
- [[#627](https://github.com/sonata-project/EntityAuditBundle/pull/627)] Symfony 7.1 deprecation about `Symfony\Component\HttpKernel\DependencyInjection\Extension` usage ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#624](https://github.com/sonata-project/EntityAuditBundle/pull/624)] Crashing when an invalid value is passed as OneTo* relation. ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.17.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.17.0...1.17.1) - 2024-04-17
### Fixed
- [[#617](https://github.com/sonata-project/EntityAuditBundle/pull/617)] Fix getting table name when the table schema is an empty string ([@X-Coder264](https://github.com/X-Coder264))
- [[#615](https://github.com/sonata-project/EntityAuditBundle/pull/615)] Allow multiple relationships to the same target entity. ([@mikeyudin](https://github.com/mikeyudin))

## [1.17.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.16.1...1.17.0) - 2024-04-13
### Changed
- [[#612](https://github.com/sonata-project/EntityAuditBundle/pull/612)] Multiple queries to tables have been replaced with a single one ([@SavageDays](https://github.com/SavageDays))

## [1.16.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.16.0...1.16.1) - 2024-01-22
### Fixed
- [[#599](https://github.com/sonata-project/EntityAuditBundle/pull/599)] Objects now no longer show as different when their values are the same. This restores some of the old behaviour of the EntityAuditBundle ([@befresh-mweimerskirch](https://github.com/befresh-mweimerskirch))

## [1.16.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.15.0...1.16.0) - 2023-12-04
### Added
- [[#592](https://github.com/sonata-project/EntityAuditBundle/pull/592)] Support for Symfony 7 ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.15.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.14.1...1.15.0) - 2023-09-28
### Added
- [[#587](https://github.com/sonata-project/EntityAuditBundle/pull/587)] Added the `disable_foreign_keys` parameter, which disables the creation of foreign keys. ([@SavageDays](https://github.com/SavageDays))

## [1.14.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.14.0...1.14.1) - 2023-08-06
### Fixed
- [[#583](https://github.com/sonata-project/EntityAuditBundle/pull/583)] Deprecation of Event Subscribers on Symfony 6.3. They now uses Event Listeners ([@Hanmac](https://github.com/Hanmac))

## [1.14.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.13.0...1.14.0) - 2023-04-24
### Removed
- [[#564](https://github.com/sonata-project/EntityAuditBundle/pull/564)] Support for Symfony 4 ([@jordisala1991](https://github.com/jordisala1991))
- [[#564](https://github.com/sonata-project/EntityAuditBundle/pull/564)] Support for Twig 2 ([@jordisala1991](https://github.com/jordisala1991))

## [1.13.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.12.0...1.13.0) - 2023-04-09
### Fixed
- [[#555](https://github.com/sonata-project/EntityAuditBundle/pull/555)] Deprecations from Doctrine DBAL and ORM ([@jordisala1991](https://github.com/jordisala1991))

### Removed
- [[#557](https://github.com/sonata-project/EntityAuditBundle/pull/557)] Support for `doctrine/doctrine-bundle` < 2.7 ([@jordisala1991](https://github.com/jordisala1991))
- [[#554](https://github.com/sonata-project/EntityAuditBundle/pull/554)] Requirement for `doctrine/common` ([@jordisala1991](https://github.com/jordisala1991))
- [[#551](https://github.com/sonata-project/EntityAuditBundle/pull/551)] Support for PHP 7.4 ([@SonataCI](https://github.com/SonataCI))
- [[#551](https://github.com/sonata-project/EntityAuditBundle/pull/551)] Support for Symfony 6.0 and 6.1 ([@SonataCI](https://github.com/SonataCI))

## [1.12.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.11.0...1.12.0) - 2023-02-28
### Added
- [[#547](https://github.com/sonata-project/EntityAuditBundle/pull/547)] Add compatibility with `doctrine/collections` ^2.0 ([@jordisala1991](https://github.com/jordisala1991))

### Removed
- [[#549](https://github.com/sonata-project/EntityAuditBundle/pull/549)] Drop support for `doctrine/dbal` ^2.0. ([@jordisala1991](https://github.com/jordisala1991))
- [[#549](https://github.com/sonata-project/EntityAuditBundle/pull/549)] Drop support for `doctrine/persistence` ^2.0. ([@jordisala1991](https://github.com/jordisala1991))

## [1.11.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.10.1...1.11.0) - 2023-02-20
### Added
- [[#541](https://github.com/sonata-project/EntityAuditBundle/pull/541)] Support for Doctrine Event manager v2 ([@X-Coder264](https://github.com/X-Coder264))

### Fixed
- [[#543](https://github.com/sonata-project/EntityAuditBundle/pull/543)] Clear extra updates array to prevent memory leak ([@X-Coder264](https://github.com/X-Coder264))
- [[#544](https://github.com/sonata-project/EntityAuditBundle/pull/544)] Clear entity cache to prevent memory leak ([@X-Coder264](https://github.com/X-Coder264))

## [1.10.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.10.0...1.10.1) - 2023-02-14
### Fixed
- [[#539](https://github.com/sonata-project/EntityAuditBundle/pull/539)] Not null constraint violation during many to many association audit recording ([@X-Coder264](https://github.com/X-Coder264))

## [1.10.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.9.1...1.10.0) - 2023-02-13
### Added
- [[#536](https://github.com/sonata-project/EntityAuditBundle/pull/536)] Support for PSR ClockInterface ([@X-Coder264](https://github.com/X-Coder264))

### Fixed
- [[#537](https://github.com/sonata-project/EntityAuditBundle/pull/537)] Schema creation for self referencing many to many association with class table inheritance ([@X-Coder264](https://github.com/X-Coder264))

## [1.9.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.9.0...1.9.1) - 2023-02-06
### Fixed
- [[#534](https://github.com/sonata-project/EntityAuditBundle/pull/534)] Audit query for Doctrine ORM >= 2.14.1 for entities with enumType column mapping ([@X-Coder264](https://github.com/X-Coder264))

## [1.9.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.8.0...1.9.0) - 2022-10-10
### Added
- [[#509](https://github.com/sonata-project/EntityAuditBundle/pull/509)] Support for ManyToMany ([@pietaj](https://github.com/pietaj))

### Removed
- [[#497](https://github.com/sonata-project/EntityAuditBundle/pull/497)] Support of Symfony 5.3 ([@franmomu](https://github.com/franmomu))

## [1.8.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.7.0...1.8.0) - 2022-05-21
### Added
- [[#488](https://github.com/sonata-project/EntityAuditBundle/pull/488)] Specify iterable types ([@franmomu](https://github.com/franmomu))
- [[#490](https://github.com/sonata-project/EntityAuditBundle/pull/490)] Added support for `doctrine/persistence` 3 ([@franmomu](https://github.com/franmomu))

### Changed
- [[#488](https://github.com/sonata-project/EntityAuditBundle/pull/488)] Make `AuditReader` not generic ([@franmomu](https://github.com/franmomu))
- [[#484](https://github.com/sonata-project/EntityAuditBundle/pull/484)] Change composer license to MIT ([@mpoiriert](https://github.com/mpoiriert))

### Fixed
- [[#491](https://github.com/sonata-project/EntityAuditBundle/pull/491)] Deprecation warning about using `SQLResultCasing` internal trait ([@franmomu](https://github.com/franmomu))
- [[#486](https://github.com/sonata-project/EntityAuditBundle/pull/486)] Fixed some phpdoc types ([@franmomu](https://github.com/franmomu))

## [1.7.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.6.1...1.7.0) - 2022-02-03
### Deprecated
- [[#472](https://github.com/sonata-project/EntityAuditBundle/pull/472)] Constructing `TokenStorageUsernameCallable` with an instance of `Container`, use an instance of `TokenStorageInterface` instead ([@franmomu](https://github.com/franmomu))

### Fixed
- [[#469](https://github.com/sonata-project/EntityAuditBundle/pull/469)] Fixed `AuditReader` to process to-many associations using IDs with custom types ([@webmozart](https://github.com/webmozart))
- [[#472](https://github.com/sonata-project/EntityAuditBundle/pull/472)] Fixed service id of `ViewEntityAction` ([@franmomu](https://github.com/franmomu))

### Removed
- [[#472](https://github.com/sonata-project/EntityAuditBundle/pull/472)] Support for `doctrine/orm` < 1.12.8 ([@franmomu](https://github.com/franmomu))

## [1.6.1](https://github.com/sonata-project/EntityAuditBundle/compare/1.6.0...1.6.1) - 2021-12-04
### Fixed
- [[#459](https://github.com/sonata-project/EntityAuditBundle/pull/459)] Re add support for nullable username in a revision ([@VincentLanglet](https://github.com/VincentLanglet))

## [1.6.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.5.0...1.6.0) - 2021-10-28
### Added
- [[#444](https://github.com/sonata-project/EntityAuditBundle/pull/444)] Added support for Doctrine DBAL 3 ([@jordisala1991](https://github.com/jordisala1991))

### Changed
- [[#413](https://github.com/sonata-project/EntityAuditBundle/pull/413)] Several docblock types detected by PHPStan ([@phansys](https://github.com/phansys))

### Fixed
- [[#413](https://github.com/sonata-project/EntityAuditBundle/pull/413)] Return value at `TokenStorageUsernameCallable::__invoke()` ([@phansys](https://github.com/phansys))
- [[#452](https://github.com/sonata-project/EntityAuditBundle/pull/452)] `InvalidRevisionException` exception handling when a revision is not found at `ViewRevisionAction::__invoke()` ([@phansys](https://github.com/phansys))
- [[#415](https://github.com/sonata-project/EntityAuditBundle/pull/415)] Missing dependencies required by this package ([@phansys](https://github.com/phansys))
- [[#446](https://github.com/sonata-project/EntityAuditBundle/pull/446)] Avoid passing unknown options to a column during its creation ([@simonberger](https://github.com/simonberger))

### Removed
- [[#451](https://github.com/sonata-project/EntityAuditBundle/pull/451)] Removed support for Doctrine ORM < 2.10 ([@jordisala1991](https://github.com/jordisala1991))
- [[#451](https://github.com/sonata-project/EntityAuditBundle/pull/451)] Removed support for Doctrine DBAL < 2.13 ([@jordisala1991](https://github.com/jordisala1991))

## [1.5.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.4.0...1.5.0) - 2021-09-21
### Added
- [[#439](https://github.com/sonata-project/EntityAuditBundle/pull/439)] Added explicit dependencies with Doctrine and Symfony ([@jordisala1991](https://github.com/jordisala1991))
- [[#439](https://github.com/sonata-project/EntityAuditBundle/pull/439)] Added support for Symfony 6 ([@jordisala1991](https://github.com/jordisala1991))

### Fixed
- [[#414](https://github.com/sonata-project/EntityAuditBundle/pull/414)] Wrong return type declarations in `AuditedCollection` methods ([@phansys](https://github.com/phansys))
- [[#414](https://github.com/sonata-project/EntityAuditBundle/pull/414)] Obsolete check in `AuditReader::createEntity()` ([@phansys](https://github.com/phansys))

### Removed
- [[#439](https://github.com/sonata-project/EntityAuditBundle/pull/439)] Removed support for Symfony 5.2 ([@jordisala1991](https://github.com/jordisala1991))

## [1.4.0](https://github.com/sonata-project/EntityAuditBundle/compare/1.3.2...1.4.0) - 2021-07-21
### Added
- [[#408](https://github.com/sonata-project/EntityAuditBundle/pull/408)] Foreign key constraint for the relation between the entity audit tables and the revisions index ([@phansys](https://github.com/phansys))

### Deprecated
- [[#408](https://github.com/sonata-project/EntityAuditBundle/pull/408)] `CreateSchemaListener::postGenerateSchema()` method ([@phansys](https://github.com/phansys))

### Fixed
- [[#408](https://github.com/sonata-project/EntityAuditBundle/pull/408)] Orphan records between the entity audit tables and the revisions index ([@phansys](https://github.com/phansys))
- [[#406](https://github.com/sonata-project/EntityAuditBundle/pull/406)] The CompareAction route is now working ([@BurningDog](https://github.com/BurningDog))

### Removed
- [[#408](https://github.com/sonata-project/EntityAuditBundle/pull/408)] Listening for the "postGenerateSchema" event at `CreateSchemaListener` ([@phansys](https://github.com/phansys))

## [1.3.2](https://github.com/sonata-project/EntityAuditBundle/compare/1.3.1...1.3.2) - 2021-06-13
### Fixed
- [[#398](https://github.com/sonata-project/EntityAuditBundle/pull/398)] Fix missing space in identifier WHERE clausule if the entity has multiple columns as the primary key ([@Vantomas](https://github.com/Vantomas))

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
