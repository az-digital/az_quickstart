CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Process Plugins
 * Source Plugins
 * Useful Modules
 * Usage Notes


INTRODUCTION
------------


This module is meant to be used instead of, or in combination with, the core
`migrate_drupal` module.
Custom Migration from Drupal 7 to Drupal 9

In order to migrate from Quickstart 1 or another distribution of Drupal 7,
please use the [az-digital/az-quickstart-scaffolding repo](https://github.com/az-digital/az-quickstart-scaffolding) to create a new
migration project.

Follow along with that repository's README until you get to the Migration setup
in Lando section and then follow these steps.

1. Download an archive of your Drupal 7 site's database.
2. Place the database dump file in the the new site's docroot.
3. Download an archive of you Drupal 7 site's files directory. This is usually
   sites/default/files
4. Set the site up for migration with the follow lando commands from root of
   your new migration project.
  - `lando start`
  - `lando install`
  - `lando migrate-setup`
5. Copy your downloaded Drupal 7 site's files into
   `/web/sites/default/files/az_migrate`
6. Import your Drupal 7 site's database archive with lando replacing
   `<filename>` with the path to your archive in the docroot.
  - `lando migrate-db-import <filename>`
7. Check that everything worked
  - `lando drush migrate:status --group=az_migration`
You should now see a migration status table depicting the upcoming migration.
8. Import the `az_migration` group.
  - `lando drush migrate:import --group=az_migration`
9. Check the migration log and troubleshoot any errors.
10. Log into the lando site and check for imported content.

You can skip most of the rest of this README unless you run into trouble.

REQUIREMENTS
------------

Quickstart 1 source website.
Access to Quickstart 1 database credentials.
Migration Quickstart 2 destination site should have database and http
access to the QuickStart 1 source website.

INSTALLATION
------------

  * Install the module using the below command.

  ```
    drush en az_migration
  ```


CONFIGURATION
-------------

  ## Configure the settings.php to connect Drupal 7 database.

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

  ## Before running the file migration, please configure the file location.

  Files can be migrated in two ways as depicted below:

  #### 1. Copy the file  "sites/default/files/migrate_file" folder.

  Then set the configuration as below:

  ```
  drush cset az_migration.settings migrate_d7_filebasepath ""
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

  ## Post migration steps

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

PROCESS PLUGINS
------------

These process plugins are provided by this module.

	Reuseable plugins:
	- `az_migrated_path_lookup`
	- `text_format_recognizer`
	- `paragraphs_mapping_flexible_page`

	"Internal" (non-reusable) plugins:
	- `paragraphs_callout_field_merge`
	- `paragraphs_chunks_view_display_mapping`
	- `paragraphs_column_image_field_merge`
	- `paragraphs_extra_info_field_merge`
	- `paragraphs_file_download_field_merge`
	- `paragraphs_fw_media_row_field_merge`
	- `az_paragraphs_media_caption`

Process plugins provided by `az_paragraphs` module.
	Reuseable plugins:
  - paragraphs_behavior_settings (Deprecated: use `az_paragraphs_behavior_settings`)
  - az_paragraphs_behavior_settings

SOURCE PLUGINS
------------

These source plugins are provided by this module.

- `az_file_migration`
- `az_node`
- `az_paragraphs_item`

USEFUL MODULES
------------

When debugging migrations, the [Migrate
Devel](https://www.drupal.org/project/migrate_devel) module can be used to print source
and destination values on the screen when importing or rolling back via `drush`.

USAGE NOTES
-----------

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

#### 1. User Migration

Source site pre-migration tasks :

* Block any users you don’t want to migrate.
* Check for any custom or overridden fields on users.

During the migration we have consider below mapping suggestions :

* D7 Administrator role will be migrated to D9 Administrator role
* D7 Content administrator and Content editor roles will be migrated to D9 Authenticated user role
* D7 blocked users will not be migrated

```
composer update --lock
```

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

#### 2. All File Migration

Source site pre-migration tasks :

* Delete any files you don’t want migrated.


Migrate the related files using the below command :
```
drush mim az_files
```

To rollback the migrated file :
```
drush mr az_files
```

#### 3. All Media Migration

Source site pre-migration tasks :

* Delete any files you don’t want migrated.
* Check for any custom or overridden fields on file_entities.
* Check for any custom file entity types.

Migrate the related files using the below command :
```
drush mim az_media
```

To rollback the migrated file :
```
drush mr az_media
```

## Migrate the Person Content type.

#### 1. Person Category Migration

Source site pre-migration tasks :

* Delete any terms you don’t want migrated.
* Check for any custom or overridden fields on uaqs_person_category taxonomy.
* Check for any custom or overridden fields on uaqs_person_category_secondary taxonomy.

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

#### 2. Person Content Migration

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

## Migrate the Event Content type.

Dependencies :

* User Migration
* File Migration
* Media Migration
* Contact Migration
* Event Category Migration
* Event Module

#### 1. Event Category Migration.

Source site pre-migration tasks :

* Delete any categories you don’t want migrated.
* Check for any custom or overridden fields on event_categories taxonomy.

Migrate event categories using the below command :
```
drush mim az_event_categories
```

#### 2. Event Content Migration.

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


## Migrate the News Content type.

Dependencies :

* User Migration
* File Migration
* Media Migration
* Paragraph Migration
* Contact Migration
* News Tag Migration
* News Module

#### 1. News Tags Migration.

Source site pre-migration tasks :

* Delete any news tags you don’t want migrated.
* Check for any custom or overridden fields on uaqs_news_tags taxonomy.


Migrate news tags using the below command :
```
drush mim az_news_tags
```

To rollback the migrated news tags :
```
drush mr az_news_tags
```

#### 2. Contact Migration.

Migrate contact paragraphs using the below command :

```
drush mim az_paragraph_contact
```

To rollback the migrated contacts :
```
drush mr az_paragraph_contact
```

#### 3. Headed Text Migration.

Migrate headed text paragraphs using the below command :

```
drush mim az_paragraph_headed_text
```

To rollback the migrated headed texts :
```
drush mr az_paragraph_headed_text
```

#### 4. Extra Info Migration.

Migrate extra info paragraphs using the below command :

```
drush mim az_paragraph_extra_info
```

To rollback the migrated extra info paragraphs :
```
drush mr az_paragraph_extra_info
```

#### 5. File Download Migration.

Migrate file download paragraphs using the below command :

```
drush mim az_paragraph_file_download
```

To rollback the migrated file download paragraphs :
```
drush mr az_paragraph_file_download
```

#### 6. Card Deck Migration.
Notes:

This migration only imports the first link for cards from the multi-value link field in Quickstart v1. If there are multiple links on a card, you can edit the migrated card after the migration and add the links to the text area as HTML instead of using the link field.

Source site pre-migration tasks:

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

#### 7. Column Image Migration.

Migrate column image paragraphs using the below command :

```
drush mim az_paragraph_column_image
```

To rollback the migrated column image paragraphs :
```
drush mr az_paragraph_column_image
```

#### 8. News Content Migration.

Source site pre-migration tasks :

* Delete any news content you don’t want migrated.
* Check for any custom or overridden fields on uaqs_news content type.

Migrate news content using the below command :
```
drush mim az_node_news
```

To rollback the migrated news content :
```
drush mr az_node_news
```

## Carousel Item Migration

Migrate carousel item using the below command :
```
drush mim az_node_carousel
```

To rollback the  carousel item using the below command :
```
drush mr az_node_carousel
```
## Menu Links Migration

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
