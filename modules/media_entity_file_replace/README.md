# Media Entity File Replace

This module allows content editors to easily replace the source files associated
with file-based media types (like "Document"). The replacement file overwrites
the existing file, keeping the same filename and path, which is usually what
content editors want to do when performing a file replacement.

- For a full description of the module, visit the
  [project page](https://www.drupal.org/project/media_entity_file_replace).

- To submit bug reports and feature suggestions, or track changes
  [issue queue](https://www.drupal.org/project/issues/media_entity_file_replace).


## Contents of this file

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

The media entity file replace module requires the (Drupal core) Media module
to be enabled.

- [Media](https://www.drupal.org/project/media)


## Installation

Install as you would normally install a contributed Drupal module. See
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.


## Configuration

- Browse to `/admin/structure/media` location.
- From operations section, select 'Manage form display' for any file-based
  media types (Audio, Document, Image, etc).
- Enable the "Replace file" form display widget. It's best to place it directly
  beneath the existing "File" or "Image" widget.


## Maintainers

- Brian Osborne - [bkosborne](https://www.drupal.org/u/bkosborne)
- Adam Nagy - [joevagyok](https://www.drupal.org/u/joevagyok)
