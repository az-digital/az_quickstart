# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [Unreleased][unreleased]

## [1.3.0] - 2023-11-29
### Changed

 - Level arguments for `hasRecord*()` methods are now optional (#5)

### Fixed

 - Fixed PHP 8.2 compatibility when handling exception type records (#4)

## [1.2.0] - 2023-03-14
### Added

 - Added support for custom string- and int-based log levels (#2)

### Changed

 - Minimum PHP version is now 8.0

### Fixed

 - Fixed `log()` not throwing the correct exception when an invalid log level is passed

### Removed

 - Removed support for PHP 7.4

## [1.1.0] - 2022-04-27

### Fixed

 - Fixed incorrect parameters in magic method docblocks
 - Fixed missing `string` type to `hasRecordThatPasses()`

## [1.0.0] - 2022-02-17

**Initial commit!**

[unreleased]: https://github.com/colinodell/psr-testlogger/compare/v1.3.0...main
[1.3.0]: https://github.com/colinodell/psr-testlogger/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/colinodell/psr-testlogger/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/colinodell/psr-testlogger/compare/v1.0.0...main
[1.0.0]: https://github.com/colinodell/psr-testlogger/releases/tag/v1.0.0
