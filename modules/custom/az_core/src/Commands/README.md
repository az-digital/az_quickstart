# AZ Core Config Commands

A Drupal module that provides custom Drush commands for managing the configuration of the AZ Quickstart profile. The module adds missing permissions from the AZ Quickstart profile to the active site.

## Requirements
- Drupal 8 or 9
- Drush

## Installation
- Install the module as you would normally install any Drupal module.
- Enable the module using the Drupal administration interface or Drush.
- After installation, you can access the custom Drush commands provided by this module.

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
