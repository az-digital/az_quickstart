# AZ Core Config Commands

AZ Core provides Drush commands to help with managing configuration on AZ Quickstart websites.

## Requirements
- Drupal 9 or 10
- Drush

## Installation
This module is automatically included in the az_quickstart install profile and enabled by default. This custom Drush command will work on any installed Quickstart 2 website.

## Usage
This module provides the following Drush command:
`drush az-core-config-add-permissions`
This command will add missing installation profile permissions to the active site.

You can optionally add the -y flag to accept adding all missing distribution permissions.

## Class details
- Namespace: Drupal\az_core\Commands
- Class name: AZCoreConfigCommands
- Dependencies: ConfigCollector, ModuleExtensionList

## Method details
### __construct(ConfigCollector $configCollector, ModuleExtensionList $extensionLister)
Constructs a new AZCoreConfigCommands object.

### addMissingPermissions()
A custom Drush command to add missing installation profile permissions.
