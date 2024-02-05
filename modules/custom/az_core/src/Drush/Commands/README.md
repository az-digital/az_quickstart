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

### Usage

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

The default output will count each entity type by bundle, and output in a table by default.

To output in other formats, use `drush az-entity-list:list --help`

```
 ------------------------ -------------------------------- ------------------- ------- 
  Entity type              Bundle                           Provider            Count  
 ------------------------ -------------------------------- ------------------- ------- 
  block                    block                            block               47     
  block_content            az_custom_menu_block             block_content       3      
  block_content_type       block_content_type               block_content       4      
  config_snapshot          config_snapshot                  config_snapshot     76     
  crop_type                crop_type                        crop                8      
  editor                   editor                           editor              2      
  embed_button             embed_button                     embed               3      
  field_config             field_config                     field               85     
  field_storage_config     field_storage_config             field               63     
  file                     file                             file                41     
  filter_format            filter_format                    filter              5      
  google_tag_container     google_tag_container             google_tag          1      
  image_style              image_style                      image               27     
  linkit_profile           linkit_profile                   linkit              2      
  media_type               media_type                       media               5      
  media                    az_image                         media               35     
  media                    az_remote_video                  media               6      
  menu_link_content        menu_link_content                menu_link_content   34     
  metatag_defaults         metatag_defaults                 metatag             7      
  migration_group          migration_group                  migrate_plus        1      
  node_type                node_type                        node                4      
  node                     az_event                         node                23     
  node                     az_flexible_page                 node                22     
  node                     az_news                          node                5      
  node                     az_person                        node                13     
  path_alias               path_alias                       path_alias          144    
  redirect                 redirect                         redirect            1      
  responsive_image_style   responsive_image_style           responsive_image    1      
  search_page              search_page                      search              4      
  shortcut                 default                          shortcut            2      
  shortcut_set             shortcut_set                     shortcut            1      
  smart_date_format        smart_date_format                smart_date          4      
  smart_date_rule          smart_date_rule                  smart_date_recur    9      
  action                   action                           system              30     
  menu                     menu                             system              11     
  taxonomy_vocabulary      taxonomy_vocabulary              taxonomy            7      
  taxonomy_term            az_event_categories              taxonomy            23     
  taxonomy_term            az_news_tags                     taxonomy            4      
  taxonomy_term            az_page_categories               taxonomy            12     
  taxonomy_term            az_person_categories             taxonomy            19     
  taxonomy_term            az_person_categories_secondary   taxonomy            11     
  tour                     tour                             tour                3      
  user_role                user_role                        user                7      
  user                     user                             user                2      
  workflow                 workflow                         workflows           1      
  pathauto_pattern         pathauto_pattern                 pathauto            6      
  xmlsitemap               xmlsitemap                       xmlsitemap          1      
  view                     view                             views               22     
  paragraphs_type          paragraphs_type                  paragraphs          9      
  paragraph                az_cards                         paragraphs          4      
  paragraph                az_splitscreen                   paragraphs          21     
  paragraph                az_text                          paragraphs          17     
  paragraph                az_text_background               paragraphs          9      
  paragraph                az_text_media                    paragraphs          14     
  paragraph                az_view_reference                paragraphs          4      
  base_field_override      base_field_override              core                5      
  date_format              date_format                      core                13     
  entity_form_display      entity_form_display              core                29     
  entity_form_mode         entity_form_mode                 core                2      
  entity_view_display      entity_view_display              core                73     
  entity_view_mode         entity_view_mode                 core                47     
 ------------------------ -------------------------------- ------------------- ------- 

```
