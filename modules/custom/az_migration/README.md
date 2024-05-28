# Quickstart Migration Module

## Contents

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Getting started](#getting-started)
 * [Post migration steps](#post-migration-steps)
 * [Writing custom migrations](#writing-custom-migrations)
 * [Quickstart migrations usage notes](#quickstart-migrations-usage-notes)
 * [Migrate plugins](#migrate-plugins)
 * [Useful modules](#useful-modules)

# Introduction

This module provides a collection of ready to use migrations for migrating content from UA Quickstart (Quickstart 1) to Arizona Quickstart (Quickstart 2) as well as a library of migrate process and source plugins to simplify the creation of custom migrations for migrating other content to Quickstart 2.  This module is designed to be used either as an alternative to or in combination with the `migrate_drupal` module provided by Drupal core.


# Requirements

- An archive of a Quickstart 1 (or Drupal 7) site's database or database access credentials
- An archive of a Quickstart 1 (or Drupal 7) site's files directory or HTTP access to the files
- Access to environment to create a Quickstart 2 site on (we recommend a local development environment to begin with)

# Getting started

## Using the Quickstart scaffolding repo (recommended)

In order to simplify the process of getting started with Quickstart migrations, the Arizona Digital team has added some migration-specific features to the [Quickstart Scaffolding Repo](https://github.com/az-digital/az-quickstart-scaffolding).  This repository is designed to be used as a composer project template for new Quickstart 2 website projects.

To get started with a Quickstart 2 migration using the scaffolding repo as a project template, follow the steps in that repository's [README](https://github.com/az-digital/az-quickstart-scaffolding#readme) until you get to the "Migration setup in Lando" section and then follow these steps:

_Note: If your source site has workbench moderation enabled and it is using it
to create a workflow with any content type that has paragraphs (azqs_news,
azqs_flexible_page), you should use this command to allow archived paragraph
revisions to be migrated. See https://github.com/az-digital/az_quickstart/issues/1763_

```
drush cset az_migration.settings allow_archived_paragraphs true
```

1. Download an archive (dump) of your source site's database.
2. Place the database dump file into the (project) root directory of your new migration project directory.
3. Download an archive of your source site's files directory. This is usually in the
   `sites/default/files` directory of your website
4. Set the site up for migration by running lando commands from root of your new migration project.
   ```
   lando start
   lando install
   lando migrate-setup
   ```
5. Copy your downloaded Drupal 7 site's files into
   `/web/sites/default/files/migrate_files`.
6. Import your Drupal 7 site's database archive with lando replacing
   `<filename>` with the path to your database archive in the root of your new migration project.
   ```
   lando migrate-db-import <filename>
   ```
7. Check the status of the migrations provided by the `az_migration` module.  You should see a migration status table listing the migrations and the number of rows associated with each migration in the source site database.
    ```
    lando drush migrate:status --group=az_migration
    ```
8. Import the `az_migration` group.
    ```
    lando drush migrate:import --group=az_migration
    ```
9. Check output of the migration import cammand take note of any errors.
10. Log into the lando site and review the imported content.

You can skip most of the rest of this README unless you run into trouble.


## Manual installation and configuration

  ### Module installation

  * Install the module using the below command.

  ```
    drush install az_migration
  ```

  ### Configure the settings.php to connect source database

  Please modify and add the below connection string to the settings.php

  ```
  $databases['migrate']['default'] = [
    'driver' => 'mysql',
    'namespace' => 'Drupal\Core\Database\Driver\mysql',
    'database' => 'databasename',
    'username' => 'databaseusername',
    'password' => 'databasepassword',
    'port' => 'databaseport',
    'host' => 'localhost',
    'prefix' => '',
  ];
  ```

  ### Before running the file migration, please configure the file location.

  Files can be migrated in two ways as depicted below:

  #### 1. Copy the source site files to the "sites/default/files/migrate_file" folder.

  Then set the configuration as below:

  ```
  drush cset az_migration.settings migrate_d7_filebasepath " "
  drush cset az_migration.settings migrate_d7_public_path "sites/default/files/migrate_file"
  ```

  #### 2. Can directly configure the remote url for the file as below

  Set the configuration as below :
  Example filebasepath: `example.arizona.edu`

  ```
  drush cset az_migration.settings migrate_d7_protocol "https"
  drush cset az_migration.settings migrate_d7_filebasepath "example.arizona.edu"
  drush cset az_migration.settings migrate_d7_public_path "sites/default/files"
  ```

# Post migration steps

After migrating content, the `migrate` database connection should be removed
from your site's `settings.php` file.  This will prevent the issues with the
migrations being loaded by the Drupal plugin system and interfering with
Quickstart features that use the migrate API (e.g. Quickstart News Feeds and
Quickstart Global Footer).  This is especially important if the source site
has been shut down or is on an environment that suspends idle
servers/containers (e.g. Pantheon).

The `migrate` database connection can be added back to the site's
`settings.php` file as needed (e.g. if additional content migrations need to
be run or rerun after the initial migration is complete) as long as the source
site's database is available.



# Writing custom migrations

Nearly any architectural customization or override to a Quickstart 1 site will require a custom module with custom migrations.
Luckily this is fairly simple and only requires a structure shown below:
<webroot>
  * modules/
    * custom/
      * my_migration_module/
        * config/
          * install/
            * migrate_plus.migration_group.my_migration_group.yml
        * migrations/
          * some_migration.yml
          * another_migration.yml
        * my_migration_module.info.yml

Example [migration group file](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/config/install/migrate_plus.migration_group.az_migration.yml)

Compare source site pathauto settings to ensure new content and migrated content are consistent.


# Quickstart migrations usage notes

Usage notes for the built-in Quickstart migrations provided by this module.

### Contents
  - [User migration](#user-migration)
  - [File migration](#file-migration)
  - [Media migration](#media-migration)
  - [Person migrations](#person-migrations)
  - [Event migrations](#event-migrations)
  - [News migrations](#news-migrations)
  - [Carousel item migration](#carousel-item-migration)
  - [Paragraph migrations](#paragraph-migrations)


## User migration

### Suggested pre-migration tasks (for source site)

* Block any users you don’t want to migrate.
* Check for any custom or overridden fields on users.

During the migration we have consider below mapping suggestions :

* D7 Administrator role will be migrated to D9 Administrator role
* D7 Content administrator and Content editor roles will be migrated to D9 Authenticated user role
* D7 blocked users will not be migrated

Migrate Users using the below command :
```
drush mim az_user
```

Migrate the CAS user data:
```
drush mim az_cas_user
```

To rollback the migrated users :
```
drush mr az_user
```

## File migration

### Suggested pre-migration tasks (for source site)

* Delete any files you don’t want migrated.


Migrate the related files using the below command :
```
drush mim az_files
```

To rollback the migrated file :
```
drush mr az_files
```

## Media migration

### Suggested pre-migration tasks (for source site)

* Delete any files you don’t want migrated.
* Check for any custom or overridden fields on file_entities.
* Check for any custom file entity types.
* Take note of any file types other than `image`, `audio`, `document`, `video`
* Check pathauto patterns.

Migrate the related files using the below command :
```
drush migrate:import az_media
```

Update migrated media after updating the codebase:
```
drush cache:rebuild
drush migrate:import az_media --update
```

View messages for skipped media items:
```
drush migrate:messages az_media
```

To rollback the migrated media:
```
drush migrate:rollback az_media
```
**Note: If you have custom file_entity types that you would like to migrate, you
must create a custom migration.**

## Person migrations

### Person category migration

### Suggested pre-migration tasks (for source site)

* Delete any terms you don’t want migrated.
* Check for any custom or overridden fields on uaqs_person_category taxonomy.
* Check for any custom or overridden fields on uaqs_person_category_secondary taxonomy.
* Check pathauto patterns.

Dependencies :

* User Migration
* File Migration
* Media Migration
* Person Category Migration
* Person Secondary Category Migration
* Person Module

Migrate the related categories using the below command :
```
drush mim az_person_categories
drush mim az_person_categories_secondary
```

To rollback the migrated category :
```
drush mr az_person_categories
drush mr az_person_categories_secondary
```

### Person content migration

Source site pre-migration tasks :

* Delete any uaqs_person content you don’t want migrated.
* Check for any custom or overridden fields on uaqs_person.

Migrate person content using the below command :
```
drush mim az_node_person
```

To rollback the migrated person content :
```
drush mr az_node_person
```

## Event migrations

Dependencies :

* User Migration
* File Migration
* Media Migration
* Contact Migration
* Event Category Migration
* Event Module

### Event category migration

Source site pre-migration tasks :

* Delete any categories you don’t want migrated.
* Check for any custom or overridden fields on event_categories taxonomy.
* Check pathauto patterns.

Migrate event categories using the below command :
```
drush mim az_event_categories
```

### Event content migration.

Source site pre-migration tasks :

* Check for any custom or overridden fields on uaqs_event content type.
* Delete any events you don’t want migrated.

Migrate event content using the below command :
```
drush mim az_node_event
```

To rollback the migrated event content :
```
drush mr az_node_event
```

## News migrations

Dependencies :

* User Migration
* File Migration
* Media Migration
* Paragraph Migration
* Contact Migration
* News Tag Migration
* News Module

### News tags migration.

Source site pre-migration tasks :

* Delete any news tags you don’t want migrated.
* Check for any custom or overridden fields on uaqs_news_tags taxonomy.
* Check pathauto patterns.

Migrate news tags using the below command :
```
drush mim az_news_tags
```

To rollback the migrated news tags :
```
drush mr az_news_tags
```

### News content migration.

Source site pre-migration tasks :

* Delete any news content you don’t want migrated.
* Check for any custom or overridden fields on uaqs_news content type.
* Check pathauto patterns.

Migrate news content using the below command :
```
drush mim az_node_news
```

To rollback the migrated news content :
```
drush mr az_node_news
```

## Carousel item migration

Migrate carousel item using the below command :
```
drush mim az_node_carousel
```

To rollback the  carousel item using the below command :
```
drush mr az_node_carousel
```

## Paragraph migrations

### Contact paragraph migration

Migrate contact paragraphs using the below command :

```
drush mim az_paragraph_contact
```

To rollback the migrated contacts :
```
drush mr az_paragraph_contact
```

### Headed text paragraph migration.

Migrate headed text paragraphs using the below command :

```
drush mim az_paragraph_headed_text
```

To rollback the migrated headed texts :
```
drush mr az_paragraph_headed_text
```

### Extra info paragraph migration.

Migrate extra info paragraphs using the below command :

```
drush mim az_paragraph_extra_info
```

To rollback the migrated extra info paragraphs :
```
drush mr az_paragraph_extra_info
```

### File download paragraph migration.

Migrate file download paragraphs using the below command :

```
drush mim az_paragraph_file_download
```

To rollback the migrated file download paragraphs :
```
drush mr az_paragraph_file_download
```

### Card deck paragraph migration.
Notes:

This migration only imports the first link for cards from the multi-value link field in Quickstart v1. If there are multiple links on a card, you can edit the migrated card after the migration and add the links to the text area as HTML instead of using the link field.

### Suggested pre-migration tasks (for source site)

* Check for any custom or overridden fields on uaqs_content_chunks_card_deck paragraph type.
* Delete any card decks you don’t want migrated.

Dependencies :

* Media Migration
* Files Migration
* Quickstart Paragraphs - Cards Module (az_paragraphs_cards)

Migrate card deck paragraphs using the below command :

```
drush mim az_paragraph_card_deck
```

To rollback the migrated card decks :
```
drush mr az_paragraph_card_deck
```

### Column image paragraph migration.

Migrate column image paragraphs using the below command :

```
drush mim az_paragraph_column_image
```

To rollback the migrated column image paragraphs :
```
drush mr az_paragraph_column_image
```

## Menu links migration

### Suggested pre-migration tasks (for source site)

* Prepare your menus for migration by removing any unused menu items, and deleting links that do not work.
* Duplicate unpublished menu links will collide with live menu links, so it would be best to delete unpublished menu links.

Quickstart 1 menu links can be migrated using the following command:
```
drush mim az_menu_links
```

To rollback menu links, use the following command:
```
drush mr az_menu_links
```
## Exclude Node Title Migration

This migration uses migration_lookup to match source node id to the nid of
migrated content.

Optional Migration Dependencies:
Optional dependencies are honored in the correct order if the group import is run.
`drush migrate:import --group=az_migration`
If the variable doesn't exist in the source db, it will stop immediately and move on.
- az_node_carousel
- az_node_event
- az_node_flexible_page
- az_node_news
- az_node_person
- az_node_uaqs_basic_page_to_az_page

Quickstart 1 exclude node title per node settings can be migrated using the
following command:

```
drush mim az_exclude_node_title
```
You do have the option to run this migration as many times as necessary.
To update content after running additional quickstart migrations:

```
drush mim az_exclude_node_title --update
```

To rollback menu links, use the following command:
```
drush mr az_exclude_node_title
```


# Migrate plugins

Migrate plugins provided by Quickstart modules.

## Reusable plugins

These plugins are designed to be reusable in custom migrations.

### Process plugins

- [EntityEmbedProcess (az_entity_embed_process)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/EntityEmbedProcess.php)
- [MigratedPathLookup (az_migrated_path_lookup)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/MigratedPathLookup.php)
- [TextFormatRecognizer (text_format_recognizer)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/TextFormatRecognizer.php)
- [ManualMigrationLookup (az_manual_migration_lookup)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_core/src/Plugin/migrate/process/ManualMigrationLookup.php)
- [ArrayIntersect (array_intersect)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_core/src/Plugin/migrate/process/ArrayIntersect.php)
- [ParagraphsMappingFlexiblePage (paragraphs_mapping_flexible_page)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/ParagraphMappingFlexiblePage.php)
- [ParagraphsBehavior
  (paragraphs_behavior_settings)](https://github.com/az-digital/az_quickstart/blob/2.2.x/modules/custom/az_paragraphs/src/Plugin/migrate/process/ParagraphsBehavior.php)
  (**Deprecated in 2.2.x, Removed in 2.3.x: use `az_paragraphs_behavior_settings`**)
- [ParagraphsBehaviorSettings (az_paragraphs_behavior_settings)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_paragraphs/src/Plugin/migrate/process/ParagraphsBehaviorSettings.php)
- [DateTimeToSmartDate (az_drupal_date_to_smart_date)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/DateTimeToSmartDate.php)
- [ViewsReferenceMapping (az_views_reference_mapping)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/ViewsReferenceMapping.php)
- [DefaultLangcode (az_default_langcode)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/process/DefaultLangcode.php)

### Source plugins

- [AZFileHandle (az_file_migration)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/source/AZFileHandle.php)
- [AZNode (az_node)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/source/AZNode.php)
- [AZParagraphsItem (az_paragraphs_item)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/source/AZParagraphsItem.php)
- [NodeWithFieldCollection (az_node_with_field_collection)](https://github.com/az-digital/az_quickstart/blob/main/modules/custom/az_migration/src/Plugin/migrate/source/NodeWithFieldCollection.php)

## Internal plugins

These plugins are used in various built-in Quickstart migrations but were not designed with reusability in mind.

- `az_media_bundle_recognizer`
- `paragraphs_callout_field_merge`
- `paragraphs_chunks_view_display_mapping` (Deprecated: use `az_views_reference_mapping`)
- `paragraphs_column_image_field_merge`
- `paragraphs_extra_info_field_merge`
- `paragraphs_file_download_field_merge`
- `paragraphs_fw_media_row_field_merge`
- `az_paragraphs_media_caption`

# Useful modules

When debugging migrations, the [Migrate
Devel](https://www.drupal.org/project/migrate_devel) module can be used to print source
and destination values on the screen when importing or rolling back via `drush`.
