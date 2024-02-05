# Quickstart Core Drush Commands
AZ Core provides Drush commands to help with managing an AZ Quickstart website.

### Requirements

- Drupal 10
- Drush 12

### Installation

This module is automatically included in the az_quickstart install profile and enabled by default. These custom Drush commands will work on any installed Quickstart 2 website.

## AZ Core Config Commands

AZ Core provides Drush commands to help with managing configuration on AZ Quickstart websites.

### Usage
This module provides the following Drush command:
`drush az-core-config-add-permissions`
This command will add missing installation profile permissions to the active site.

You can optionally add the -y flag to accept adding all missing distribution permissions.

### Class details
- Namespace: Drupal\az_core\Commands
- Class name: AZCoreConfigCommands
- Dependencies: ConfigCollector, ModuleExtensionList

### Method details
#### __construct(ConfigCollector $configCollector, ModuleExtensionList $extensionLister)
Constructs a new AZCoreConfigCommands object.

### addMissingPermissions()
A custom Drush command to add missing installation profile permissions.

## AZ Entity List

Provides a drush command for listing entities.

### Running the drush command

List entities on an Arizona Quickstart site.

```
Options:
 --format[=FORMAT] Format the result data. Available formats: csv,json,list,null,php,print-r,sections,string,table,tsv,var_dump,var_export,xml,yaml [default: table]
 --fields=FIELDS   Available fields: Entity type (entity_type), Bundle (type), Count (count) [default: entity_type,type,count]
 --field=FIELD     Select just one field, and force format to *string*.
 --filter[=FILTER] Filter output based on provided expression
```

Topics:
drush topic docs:output-formats-filters Output formatters and filters: control the command output

Aliases: `ael`

```
lando drush az-entity-list:list
```

### Output

The default output will looks like this.

```
 --------------------- -------------------------------- -------
  Entity_type           Bundle                           Count
 --------------------- -------------------------------- -------
  block_content         az_custom_menu_block             3
  block_content         az_flexible_block                0
  block_content         az_quick_links                   0
  block_content         basic                            0
  node                  az_event                         22
  node                  az_flexible_page                 20
  node                  az_news                          5
  node                  az_person                        13
  taxonomy_vocabulary   az_enterprise_attributes         0
  taxonomy_vocabulary   az_event_categories              23
  taxonomy_vocabulary   az_news_tags                     4
  taxonomy_vocabulary   az_page_categories               12
  taxonomy_vocabulary   az_person_categories             19
  taxonomy_vocabulary   az_person_categories_secondary   11
  taxonomy_vocabulary   tags                             0
  paragraph             az_accordion                     0
  paragraph             az_cards                         4
  paragraph             az_contact                       0
  paragraph             az_photo_gallery                 0
  paragraph             az_splitscreen                   21
  paragraph             az_text                          17
  paragraph             az_text_background               9
  paragraph             az_text_media                    14
  paragraph             az_view_reference                4
 --------------------- -------------------------------- -------
```
