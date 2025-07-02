Config Ignore
=============

INTRODUCTION
------------
Ever experienced that your site's configuration was overridden,
by the configuration on the file system, when running a `drush cim`?

Not anymore!

This modules is a tool to let you keep the configuration you want, in place.

Let's say that you do would like the `system.site` configuration
(which contains that sites name, slogan, email, etc.) to remain untouched,
on your live site, no matter what the configuration in the config folder is.

Or maybe you are getting tired of having the `devel.settings`
changed every time you import configuration?

Then this module is what you are looking for.

REQUIREMENTS
------------
You will need Drupal 8.8 or higher for this module to work. If you want to
import and export config with Drush, you need Drush 10+.

INSTALLATION
------------
Consult https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8
to see how to install and manage modules in Drupal 8.

CONFIGURATION
-------------
If you go to `admin/config/development/configuration/ignore`
you will see a fairly simple interface.

Add the name of the configuration that you want to ignore.
(e.g. `system.site` to ignore site name, slogan and email site email address.)
Click the "Save configuration" button and you are good to go.

Do not ignore the `core.extension` configuration as it will prevent you
from enabling new modules with a config import. Use the `config_split` module
for environment specific modules.

If you need to bypass Config Ignore you can update/create a single configuration
by using the "Single import" feature found at
`admin/config/development/configuration/single/import`.

To deactivate `config_ignore`, include
`$settings['config_ignore_deactivate'] = TRUE;` in your settings.php file.

To change the priority of the config ignore event subscriber use:
`$settings['config_ignore_import_priority'] = -100;`
`$settings['config_ignore_export_priority'] = 100;`
The default is 0, a higher priority means that ignoring happens earlier.
On import the ignoring should probably happen rather later so that
changes from other event subscribers will be ignored.

MAINTAINERS
-----------
Current maintainers:

 * Tommy Lynge Jørgensen (TLyngeJ) - https://www.drupal.org/u/tlyngej
 * Fabian Bircher (bircher) - https://www.drupal.org/u/bircher
 * Jordan Thompson (nord102) - https://www.drupal.org/u/nord102
