INTRODUCTION
------------

Toolset and utilities for Drupal migrations.


REQUIREMENTS
------------

* [Migrate][1] (included in Drupal core)
  The core Migrate module is a soft dependency, but if it isn't installed, you
  don't need this module.

* In some cases, [Migrate Magician Rollbackable Destination Plugins][2] need
  some help, because the actual Sql migrate ID map plugin and the MigrateExecute
  class have some bugs which are blocking the migration rollback action.

  Your options are:
   * Install [Smart SQL ID Map][3] and [implement the example hook from the
     project page][4].
   * If you decide not using Smart SQL ID Map, you have to apply the following
     patches:
      * [#2845340: migrate mapping & messages table names are truncated…][5]
      * [#3227549: Sql id map plugin's getRowByDestination shouldn't return…][6]
      * [#3227660: MigrateExecutable::rollback incorrectly assumes…][7]


INSTALLATION
------------

You can install Migrate Magician as you would normally install a contributed
Drupal 8 or 9 module.


CONFIGURATION
-------------

This module does not have any configuration option.


USAGE
-----

### Migrate Magician Rollbackable Destination Plugins (`migmag_rollbackable`)

This submodule contains the rollback-capable versions of non-rollbackable
destination plugins of Drupal core. The IDs of the rollbackable versions are
prefixed with `migmag_rollbackable_`.

If you don't want to change the destination plugin IDs used in migration plugin
definitions in order for being able to roll back migrations, consider enabling
the `migmag_rollbackable_replace` module, which will change the default
destination plugin definitions to make them use the rollbackable plugin classes.


### Migrate Magician Process Plugins (`migmag_process`)

This submodule provides additional migrate process plugins. These plugins can be
used following the [Migrate API documentation about migrate process plugins][9].
The documentation of each plugin is available from
[Migrate Magician Process Plugins module's documentation page][10].


### Migrate Magician Menu Link Migrate (`migmag_menu_link_migrate`)

This submodule modifies Drupal core's menu link migrations to migrate as many
menu links as possible. You can read more about this at
[Migrate Magician Menu Link Migrate module's documentation page][11].


DOCUMENTATION
-------------

https://drupal.org/docs/contributed-modules/migrate-magician


MAINTAINERS
-----------

* Zoltán Horváth (huzooka) - https://www.drupal.org/u/huzooka

This project has been sponsored by [Acquia][8].

[1]: https://drupal.org/docs/core-modules-and-themes/core-modules/migrate-module
[2]: https://drupal.org/docs/contributed-modules/migrate-magician/migrate-magician-rollbackable-destination-plugins
[3]: https://drupal.org/project/smart_sql_idmap
[4]: https://drupal.org/project/smart_sql_idmap#s-replacing-default-plugin
[5]: https://drupal.org/i/2845340
[6]: https://drupal.org/i/3227549
[7]: https://drupal.org/i/3227660
[8]: https://acquia.com
[9]: https://drupal.org/node/2817939
[10]: https://drupal.org/node/3232102
[11]: https://drupal.org/node/3253271
