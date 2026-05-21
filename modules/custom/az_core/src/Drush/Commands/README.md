# Quickstart Core Drush Commands
AZ Core provides Drush commands to help with managing an AZ Quickstart website.

### Requirements

- Drupal 10
- Drush 12

### Installation

This module is automatically included in the az_quickstart install profile and enabled by default.
These custom Drush commands will work on any installed Quickstart 2 website.

## AZ Core Config: Add Permissions

Provides a Drush command to help with managing configuration on AZ Quickstart websites.

### Usage
This module provides the following Drush command:
`drush az-core-config-add-permissions`
This command will add missing installation profile permissions to the active site.

You can optionally add the -y flag to accept adding all missing distribution permissions.

## AZ Entity List

Provides a Drush command for listing entities.

### Usage

This module provides the following Drush command:
`drush az-entity-list:list`
This command will list entities on an Arizona Quickstart site.

```
Options:
 --format[=FORMAT] Format the result data. Available formats: csv,json,list,null,php,print-r,sections,string,table,tsv,var_dump,var_export,xml,yaml [default: table]
 --fields=FIELDS   Available fields: Entity type (entity_type), Bundle (bundle), Entity Type Provider (entity_type_provider), Count (count), [default: entity_type,bundle,entity_type_provider,count]
 --field=FIELD     Select just one field, and force format to *string*.
```

Aliases: `ael`

### Output

The default output will count each entity type by bundle, and output in a table by default.

To output in other formats, use `drush az-entity-list:list --help`

## Arizona Bootstrap 5 Block Class Updates

Provides a Drush command for updating block classes from Arizona Bootstrap 2 to Arizona Bootstrap 5 compatibility.

### Usage

This module provides the following Drush command:
`drush azbs5:update-block-classes`
This command will convert legacy Arizona Bootstrap 2 classes in block_class module settings to their Arizona Bootstrap 5 equivalents.

```
Options:
 --dry-run     Show what would be updated without making changes
 --interactive Interactively choose which blocks to update

Examples:
 azbs5:update-block-classes               Update all block classes for Arizona Bootstrap 5 compatibility
 azbs5:update-block-classes --dry-run     Preview what would be updated without making changes
 azbs5:update-block-classes --interactive Interactively choose which blocks to update
```

Aliases: `azbs5bc`

### Features

- **Dry Run Mode**: Preview changes before applying them with `--dry-run`
- **Interactive Mode**: Choose which blocks to update one by one with `--interactive`
- **Automatic Mode**: Update all eligible blocks at once (default behavior)
- **Smart Conversion**: Uses existing `AZBootstrapMarkupConverter::CLASS_MAP` for consistent conversions
- **Targeted Updates**: Only updates blocks that actually have classes requiring conversion **Warning:** This is meant to be run only once in update mode. You can do as many dry runs as you like, but actual updates should only be run once.

### Output

The command scans all block configurations and identifies blocks with `block_class` settings that contain Arizona Bootstrap 2 classes. It then converts these to their Arizona Bootstrap 5 equivalents and provides detailed feedback on what was changed.

