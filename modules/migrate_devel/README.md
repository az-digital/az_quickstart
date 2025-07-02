CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Migrate Devel module adds utilities to help out developers when creating
migrations.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/migrate_devel

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/migrate_devel


RECOMMENDED MODULES
-------------------

 * [migrate_tools](https://www.drupal.org/project/migrate_tools)
 * [migrate_run](https://www.drupal.org/project/migrate_run)
 * [config_update](https://www.drupal.org/project/config_update)
 * [migrate_plus](https://www.drupal.org/project/migrate_plus)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

Options are:

* `--migrate-debug` - Prints out rows as they run.
  Can be used in `migrate-import` or `migrate-status` and will revert
  existing migrations to the default and clear the cache for them.
  This requires [config_update](https://www.drupal.org/project/config_update)
  if you use [migrate_plus](https://www.drupal.org/project/migrate_plus)
  because migrations go into config.
* `--migrate-debug-pre` - Same as above before the process is run on the row.
  Can be used in `migrate-import`.


MAINTAINERS
-----------

Current maintainers:
 * [Andrew Macpherson)](https://www.drupal.org/u/andrewmacpherson)

Former maintainers:
 * [Dave Wikoff (Derimagia)](https://www.drupal.org/u/derimagia)

This project is sponsored by:
 * [Mindgrub Technologies](https://www.drupal.org/mindgrub-technologies) - Derimagia's work on the 8.x-1.x branch.
