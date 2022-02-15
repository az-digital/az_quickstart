CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usage Notes


INTRODUCTION
------------

Custom Migration from Drupal 7 to Drupal 9

  * Person Content Type:

    All the person categories and files can be migrated using this module.
    Person data can be migrated by follow the below steps.

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

  Please add the below connection string to the settings.php

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

  File can be migrated in two ways as below -

  #### 1. Copy the file  "sites/default/files/migrate_file" folder.

  Then Set the configuration as below :

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
- az_node_carousel
- az_node_event
- az_node_flexible_page
- az_node_news
- az_node_person
- az_node_uaqs_basic_page_to_az_page

Quickstart 1 exclude node title per node settings can be migrated using the
following command:

```
drush mim exclude_node_title
```
To update content after running additional quickstart migrations:

```
drush mim exclude_node_title --update
```

To rollback menu links, use the following command:
```
drush mr exclude_node_title
```
