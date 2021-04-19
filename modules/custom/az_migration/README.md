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

No special requirements.


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
    'driver' => 'my_driver',
    'namespace' => 'Drupal\my_module\Driver\Database\my_driver',
    'autoload' => 'modules/my_module/src/Driver/Database/my_driver/',
    'database' => 'databasename',
    'username' => 'sqlusername',
    'password' => 'sqlpassword',
    'host' => 'localhost',
    'prefix' => '',
  ];
  ```

  ## Before running the file migration, please configure the file location.

  File can be migrate in two ways as below -

  #### 1. Copy the file  "sites/default/files/migrate_file" folder.
        
  Then Set the configuration as below :

  ```
  drush cset az_migration.settings migrate_d7_filebasepath " "
  drush cset az_migration.settings migrate_d7_public_path "sites/default/files/migrate_file"
  ```

  #### 2. Can directly configure the remote url for the file as below

  Set the configuration as below :

  ```
  drush cset az_migration.settings migrate_d7_protocol "https"
  drush cset az_migration.settings migrate_d7_filebasepath "<remote-url>"
  drush cset az_migration.settings migrate_d7_filebasepath "sites/default/files"
  ```

USAGE NOTES
-----------

## Migrate the Person Content type.

#### 1. Person Category Migration

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
#### 2. Person File Migration

Migrate the related files using the below command :
```
drush mim az_person_files
```

To rollback the migrated file :
```
drush mr az_person_files
```

#### 3. Person Content Migration

Migrate person content using the below command :
```
drush mim az_node_person
```

To rollback the migrated person content : 
```
drush mr az_node_person
```