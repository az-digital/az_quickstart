# Migrate Plus

The [Migrate Plus](https://www.drupal.org/project/migrate_plus) project provides extensions to core migration framework functionality, as well as examples.


## Table of contents

- Requirements
- Installation
- Configuration entities
- API extensions
- Process plugins
- Destination plugins
- Source plugins
- Examples
- Related modules


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration entities

- Migration plugins can be implemented as configuration entities, allowing them to flexibly be loaded, modified, and saved.
- MigrationGroup configuration entities allow migrations to be grouped in [UI and command-line tools](https://www.drupal.org/project/migrate_tools), and also allow configuration to be shared among multiple migrations.


## API extensions

- A PREPARE_ROW event is provided to allow object-oriented responses to the core prepare_row hook (modifying source data before processing begins).


## Process plugins

- `entity_lookup` - Allows you to match source data to existing Drupal 8 entities and return their IDs (primarily for populating entity reference fields).
- `entity_generate` - Extends entity_lookup to actually generate an entity from the source data where one does not already exist.
- `file_blob` - Allows you to create a file (and corresponding file entity) from blob data.
- `merge` - Allows you to merge multiple source arrays into one array.
- `skip_on_value` - Like core's skip_on_empty, but allows you to skip either the row or process upon matching (or not) a specific value.
- `str_replace` - Wrapper around str_replace, str_ireplace and preg_replace.
- `transliteration` - process strings through the transliteration service to remove language decorations and accents. Especially helpful with file names.
- Plus many, many more.


## Destination plugins

- Table - allows migrating data directly into a SQL table.


## Source plugins

- SourcePluginExtension - an abstract source plugin class providing a standard mechanism for specifying a source's IDs and fields via configuration.
- Url - a source plugin supporting file- or stream-based content (where a URL, including potentially a local filepath, points to a file containing data to be migrated). The source plugin itself simply manages the (potentially multiple) source URLs, and works with fetcher plugins to retrieve the content and parser plugins to parse it (see below).


## Additional plugin types

### `data_fetcher`

Data fetcher plugins are embedded in the Url source plugin to manage retrieval of data via a given protocol.

- File - A general-purpose fetcher for accessing any local file or stream wrapper supported by PHP's file_get_contents.
- Http - An HTTP-specific fetcher permitting usage of HTTP-specific features (such as specifying request headers).

### `data_parser`

Data parser plugins are embedded in the Url source plugin to parse the content retrieved by a fetcher.

- XML - Parses XML content using the progressive XMLReader PHP extension. Use this when XML content may be too large to be completely parsed in one go in memory.
- SimpleXML - Parses XML content using the SimpleXML PHP extension. Use this when you need full xpath support to access data elements, and the XML files are not too large.
- JSON - Parses JSON content. See [this Lullabot article](https://www.lullabot.com/articles/pull-content-from-a-remote-drupal-8-site-using-migrate-and-json-api) for an example.
- Soap - Parses SOAP feeds.

### `authentication`

Provides authentication services to the HTTP fetcher.

- Basic - supports HTTP Basic authentication.
- Digest - supports HTTP Digest authentication.
- OAuth2 - supports OAuth2 authentication over HTTP.


## Examples

Two submodules provide examples of implementing migrations.

- `migrate_example` - A carefully documented implementation of a custom migration scenario, designed to walk you through the basic concepts of the Drupal 8 migration framework.
- `migrate_example_advanced` (still in progress) - Examples of more advanced techniques for Drupal 8 migration.


## Related modules

Tools for running/managing migrations

- `migrate_tools` - General-purpose drush commands and basic UI for managing migrations.
- `migrate_upgrade` - Drush commands for running upgrades from Drupal 6 or 7 to Drupal 8+.
- `migrate_source_csv` - Source plugin for importing CSV data.
