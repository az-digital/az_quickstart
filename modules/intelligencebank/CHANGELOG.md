# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog][] and this project adheres to a
modified Semantic Versioning scheme. See the "Versioning scheme" section of the
[CONTRIBUTING][] file for more information.

[Keep a Changelog]: http://keepachangelog.com/
[CONTRIBUTING]: https://www.drupal.org/project/intelligencebank

## [Unreleased]
### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [4.1.1] - 2024-08-18

### Fixed
- 3468426: PHP warnings when using CKEditor plugin to access DAM through media library

## [4.1.0] - 2024-08-09
Introduced Link Type option to control which player is used to display embeddable video.
Improved iframe displaying by following parent width. Minor fixes.

### Added
- DC-4.0-10: Introduce Link Type option to control which player is used to display embeddable video
- DC-4.0-9: Allow specify an empty title attribute by passing an empty string
- DC-4.0-11: Add gitlab-ci default configuration
- DC-4.0-11: Add project logo
- DC-4.0-11: Add module configuration links
- DC-4.X-12: Make iframe follow parent width
- DC-4.0-10: Correct embed video url depending on link type

### Removed
- DC-4.0-11: Remove dependency on old ckeditor module
- DC-4.0-11: Remove deprecated core/underscore js library
- DC-4.0-10: Don't provide configuration form if asset is downloadable

### Fixed
- DC-4.0-11: Use correct prefix in css id/classes

## [4.0.0] - 2023-02-25
Major version based where picking assets based on Media Library module.
Added a new connector configuration option to disable Remote URLs.
Made module compatible with Drupal 10.

### Added
- DC-4.0-2 Add initial configuration
- DC-4.0-3 Add dialog mode initial UI
- DC-4.0-4: Add Asset Browser button for all media types (temporary)
- DC-4.0-5, Issue #3177500: Add UI for pre-populate the iframe URL
- DC-4.0-7, Issue #3177500: Upgrade path from 3.x to 4.x
- Issue #3295859: Add some tests for common use-cases

### Changed
- DC-4.0-1 Update of default iframe URL
- DC-4.0-2, DC-4.0-8, Issue #3177500: Improve Platform URL option label, hide ib dam embedded media tab when configured
- DC-4.0-2, DC-4.0-8, Issue #3177500: Rename and reorder login settings, add option to disable media embedding
- #3177500: Add Media Library compatible initial version

### Deprecated
- #3177500: Deprecate Entity Browser dependency
- #3177500: Deprecate direct WYSIWYG integration
### Removed

### Fixed
- DC-4.0-4, Issue #3177500: Fix missing ckeditor save callback
- DC-4.0-4, Issue #3177500: Fix inability picking asset twice or more times

## [3.0.0] - 2022-08-22
Major version that is fully compatible with IntelligenceBank API v3 based on Entity Browser module

### Added
- Issue #3295829: Enable Tugboat live previews for easier MR review

### Fixed
- Issue #3284562: IntelligenceBank API v3 removed icon retrieval
- Issue #3299165: Don't modify the entity_embed dialog in non Entity Browser contexts
- Issue #3300525r: The embed media type mapping is broken

## [8.x-2.5] - 2022-04-20
### Changed
- Updated the API doc URL

### Fixed
- Resolve #3271340 "Iframe is not"
- Scrollbar fix

## [8.x-2.4] - 2021-07-21
### Fixed
- Issue #3220068: Missing event listener for the parent frame

## [8.x-2.3] - 2021-03-18
### Changed
- Issue #3200403: Allow configure step for Download operation in Media field

## [8.x-2.2] - 2020-12-04
Required changes to use API V3 instead of V2

### Added
- Issue #3174561: Add test/staging mode option to admin UI

### Changed
- Issue #3180015: Solve authorization issue for v3 API calls

### Fixed
- Issue #3183455: Video handling issues in CKEditor

## [8.x-2.1] - 2020-09-27
Improved module stability and making it ready to use with Drupal 9

### Changed
- Issue #3173386: Allow module to be used in D8.7+ and D9

## 8.x-2.0 - 2019-03-07
### Added
- Issue #3035419: Width and Height not working in Public workflow

### Changed
- Chaos tools dependency no longer necessary
- Allow to enter only remote urls in link widget
- Add link to help page for image size options

### Fixed
- Issue #3030142: Saving existing media type IntelligenceBank DAM Embed clears values
- Add missing 'alt' attribute

[Unreleased]: https://github.com/oomphinc/risdmuseum.org/compare/4.0.0...4.0.x
[4.0.0]: https://git.drupalcode.org/project/intelligencebank/-/compare/3.0.0...4.0.0
[3.0.0]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.5...3.0.0
[8.x-2.5]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.4...8.x-2.5
[8.x-2.4]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.3...8.x-2.4
[8.x-2.3]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.2...8.x-2.3
[8.x-2.2]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.1...8.x-2.2
[8.x-2.1]: https://git.drupalcode.org/project/intelligencebank/-/compare/8.x-2.0...8.x-2.1
