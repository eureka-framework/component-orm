# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

```
## [template]
[template]: https://github.com/eureka-framework/component-orm/compare/6.0.0...master
### Changed
### Added
### Removed
```

## [6.0.0] - 2023-07
[6.0.0]: https://github.com/eureka-framework/component-orm/compare/5.4.0...6.0.0
### Removed
- Drop support of PHP 7.4 & PHP 8.0
### Changed
- Improve Generator & compiler
- Improve QueryBuilder, Repository, Mapper & Entity parts with phpdoc / phpstan types
- Lot of phpdoc fixes for phpstan
- Move Generator script file
- Update & Add some tests
- Update Makefile
- Update composer.json
- Update GitHub workflow
### Added
- Add Some Enum classes
- Add missing types (binary)
- New interface for Query Builder

--- 

## [5.5.0] - 2024-02-06
[5.5.0]: https://github.com/eureka-framework/component-orm/compare/5.4.0...5.5.0
### Changed
- Now compatible with PHP 8.3
- Update GitHub workflow

## [5.4.0] - 2023-06-14
[5.4.0]: https://github.com/eureka-framework/component-orm/compare/5.3.0...5.4.0
### Changed
- Now compatible with PHP 8.2
- Update Makefile
- Update composer.json
- Update GitHub workflow
### Added
- Add phpstan config for PHP 8.2 compatibility

## [5.3.0] - 2023-03-03
[5.3.0]: https://github.com/eureka-framework/component-orm/compare/5.2.2...5.3.0
### Changed
- Now blob have validator type set to string (because there are binary string)
### Added
- Add missing Tiny blob type

## [5.2.2] - 2023-03-01
[5.2.2]: https://github.com/eureka-framework/component-orm/compare/5.2.1...5.2.2
### Changed
- Update default empty value for medium blob from null to ''

## [5.2.1] - 2023-02-10
[5.2.1]: https://github.com/eureka-framework/component-orm/compare/5.2.0...5.2.1
### Changed
- Minor fix about default date field & CURRENT_TIMESTAMP default value
- Makefile: Disable xdebug with phpstan
- Some minor cleaning in Compiler/Field class

## [5.2.0] - 2021-08-26
[5.2.0]: https://github.com/eureka-framework/component-orm/compare/5.1.2...5.2.0
### Changed
- CI improvements (php compatibility check, makefile, github workflow)
- Now compatible with PHP 7.4, 8.0 & 8.1
- Fix phpdoc + some return type according to phpstan analysis
- Fix binding prefix with some specific chars: `(`, `)`, `,` and ` `
### Added
- phpstan for static analysis
### Removed
- phpcompatibility (no more maintained)


## [5.1.2] - 2021-08-26
[5.1.2]: https://github.com/eureka-framework/component-orm/compare/5.1.1...5.1.2
### Added
- Now allow indexing collection by specified field when use `select()`

## [5.1.1] - 2021-08-26
[5.1.1]: https://github.com/eureka-framework/component-orm/compare/5.1.0...5.1.1
### Added
- Add missing binary type for ORM generator

## [5.1.0] - 2021-08-20
[5.1.0]: https://github.com/eureka-framework/component-orm/compare/5.0.1...5.1.0
### Added
- Now Support auto-reconnection when connection is lost when execute a query
- When connection is lost during a transaction, auto-reconnection is done, but a 
   `ConnectionLostDuringTransactionException` is thrown
- Added tests according to new code

## [5.0.1] - 2020-11-06
[5.0.1]: https://github.com/eureka-framework/component-orm/compare/5.0.0...5.0.1
### Changed
- Fix empty value with varbinary fields (must be an empty string when field is not nullable)

## [5.0.0] - 2020-10-29
[5.0.0]: https://github.com/eureka-framework/component-orm/compare/4.3.0...5.0.0
### Changed
- Now require PHP 7.4
- Fix all headers
- Set some properties as private
- Fix query with cache
- Add trim on string for default field value
- Upgrade phpcodesniffer to v0.7 for composer 2.0
### Added
- Added tests
### Removed
- Remove unused code

---

## [4.3.0]
### Changed
- Now add validation configuration also in mappers
- Fix overriding for extended validation
- Update README.md to have a basic presentation of ORM & common configurations files
- Treat varbinary as varchar (string)
- Add method addWhereRaw() in query builder trait

## [4.2+] Release v4.2+
### Changed
- Update & fix generator to have better validation integration
- Update Trait to add missing generic entity generation
- Minor improvements
- Bugfix with group by queries
- Fix new generic entity with using config
- Allow multiple table prefixes
- Bugfix ORDER BY query
- Fix multiple join use on same entity/mapper
- Fix empty relation when join left
- Fix boolean values to int when bind (mariadb-10.3)
- CS Fix for PSR-12 on generated files

## [4.1] Release v4.1
### Changed
- Update phpdoc
- Fix Orm Generator (cache key suffix, double method when join many or join one)
- Better addWhere() method
### Added
- Add new method to build an entity from an array (when post form for example)
 
 
## [4.0+]
### Changed
- Upddate code
- Huge refactoring to introduce Query Builder
- Lot of modification
- Update all files & add some minor fixes / improvements
- Move declare strict type at the top of file.
- Fix phpdoc & add missing method in interface
- Update Mapper Trait to add select join method.
- Update Generator
### Added
- Add new Join trait
- Now can use selectJoin() to pre-load joined entities.
- Add validation for entities to avoid invalid state
  - Now use templating
  - Add compiler
- Add querybuilder: AbstractQuery, base query(Select, Insert, Update & Delete) & traits
- Rework interfaces & phpdocs
- Add new exceptions

## [3.x+] - 2019-04-03
### Changed
- Rename Data => Entity
- Reorganize code
- Add/Update some interface
- Update generator script
- Update mapper & entity abstracts classes
- Fix some phpdoc / @throws
- Rename Builder => Generator class
### Added
- Now can use PSR-6 cache implementation
- Add some method
- Add some exceptions
 
## [2.0]

## [1.0]
