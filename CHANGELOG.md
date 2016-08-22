# Changelog

## v0.9.2 2016-08-22
- [#209](https://github.com/simplethings/EntityAudit/pull/209) run tests against postgresql (@bendavies)
- [#208](https://github.com/simplethings/EntityAudit/pull/208) run tests againts mysql (@bendavies)
- [#206](https://github.com/simplethings/EntityAudit/pull/206) clean up base test. (@bendavies)
- [#198](https://github.com/simplethings/EntityAudit/pull/198) fix: use the type of association fields in LogRevisionsListener (@v-technologies)
- [#205](https://github.com/simplethings/EntityAudit/pull/205) Travis improvements (@bendavies)
- [#204](https://github.com/simplethings/EntityAudit/pull/204) conform to psr-4 (@bendavies)
- [#181](https://github.com/simplethings/EntityAudit/pull/181) Update CreateSchemaListener.php (@TheRatG)

## 0.9.1 / 2016-03-03

* added support for symfony/framework-bundle 3.x
* added support for PHP 7.x

#### breaking changes

* dropped support for symfony/framework-bundle < 2.7


## 0.9.0 / 2016-01-06

* added support for doctrine/orm 2.5.x
* some CS fixes
* moved test case classes in their own files

#### breaking changes

* removed support for doctrine/orm  < 2.4
* removed support for doctrine/doctrine-bundle  < 1.4
* removed support for gedmo/doctrine-extensions < 2.3.1
* removed support for symfony/framework-bundle < 2.3
