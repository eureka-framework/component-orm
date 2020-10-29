# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [5.0.0] - 2020-10-29
### Changed
 * Now require PHP 7.4
 * Fix all headers
 * Set some properties as private
 * Fix query with cache
 * Add trim on string for default field value
 * Upgrade phpcodesniffer to v0.7 for composer 2.0
### Added
 * Added tests
### Removed
 * Remove unused code

## [4.3]
### Changed
 * Now add validation configuration also in mappers
 * Fix overriding for extended validation
 * Update README.md to have a basic presentation of ORM & common configurations files
 * Treat varbinary as varchar (string)
 * Add method addWhereRaw() in query builder trait

## [4.2+] Release v4.2+
### Changed
  * Update & fix generator to have better validation integration
  * Update Trait to add missing generic entity generation
  * Minor improvements
  * Bugfix with group by queries
  * Fix new generic entity with using config
  * Allow multiple table prefixes
  * Bugfix ORDER BY query
  * Fix multiple join use on same entity/mapper
  * Fix empty relation when join left
  * Fix boolean values to int when bind (mariadb-10.3)
  * CS Fix for PSR-12 on generated files

## [4.1] Release v4.1
### Changed
 * Update phpdoc
 * Fix Orm Generator (cache key suffix, double method when join many or join one)
 * Better addWhere() method
### Added
 * Add new method to build an entity from an array (when post form for example)
 
 
## [4.0+]
### Changed
 * Upddate code
 * Huge refactoring to introduce Query Builder
 * Lot of modification
 * Update all files & add some minor fixes / improvements
 * Move declare strict type at the top of file.
 * Fix phpdoc & add missing method in interface
 * Update Mapper Trait to add select join method.
 * Update Generator
### Added
 * Add new Join trait
 * Now can use selectJoin() to pre-load joined entities.
 * Add validation for entities to avoid invalid state
  - Now use templating
  - Add compiler
 * Add querybuilder: AbstractQuery, base query(Select, Insert, Update & Delete) & traits
 * Rework interfaces & phpdocs
 * Add new exceptions

## [3.x+] - 2019-04-03
### Changed
 * Rename Data => Entity
 * Reorganize code
 * Add/Update some interface
 * Update generator script
 * Update mapper & entity abstracts classes
 * Fix some phpdoc / @throws
 * Rename Builder => Generator class
### Added
 * Now can use PSR-6 cache implementation
 * Add some method
 * Add some exceptions
 
## [2.0]

## [1.0]