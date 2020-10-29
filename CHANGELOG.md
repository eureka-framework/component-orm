# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).


## [5.0.0] - 2020-10-29
### Changed
 * New require PHP 7.4+
 * All collections now use an abstract class (menu, breadcrumb, carousel & notifications)
 * Minor fixed & improvements
 * Upgrade phpcodesniffer to v0.7 for composer 2.0
### Added
 * Session + trait for controller
 * Global collection abstract class
 * Added tests
### Removed
 * Flash notification class (now handled directly in session trait + session)
 * Compilation for phar archive: this component must be included with composer


## [3.x.y] Release v3.x.y
### Changed
 * Now require PHP 7+ (for classes Table\*)
 * Add new Table\* classes to manage table in CLI more properly
 * Allow multiple base namespace
 * Add Eureka\Component as default

## [2.x.y] Release v2.x.y
### Changed
  * Move code
  * Separate Style & Color
  * Move compilation code
  * Some update / fix
  * Update phpdoc
### Added
  * Add Table cli generation
 


## [1.0.0] - 2019-04-03
### Added
  * Add Breadcrumb item & controller aware trait
  * Add Flash notification service & controller aware trait
  * Add Menu item & controller aware trait
  * Add meta controller aware trait
  * Add Notification item