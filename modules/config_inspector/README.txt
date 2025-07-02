Configuration inspector Drupal 8/9 project (module)

INTRODUCTION
============
Configuration inspector for Drupal 8 uses the core built-in
configuration system as well as schema system to let you inspect
configuration values and the use of schemas on top of them.
This makes it possible to have a developer focused overview of
all your configuration values and do various testing and
verification tasks on your configuration schemas.

REQUIREMENTS
============
Requires Drupal core config module.

INSTALLATION
============
Installs as usually Drupal module installation via Administration
or through `composer require drupal/config_inspector`.

CONFIGURATION
=============
The module provides a menu item under Administration >
Configuration > Development > Configuration > Inspect (tab)
that lets you inspect configuration, comparing raw configuration
data with schemas; looking at configuration through the schema
in a table of summary, in a tree view or in a form.

Read more about the Drupal 8 configuration system at
http://drupal.org/node/1667894

Documentation of the schema system can be found at
http://drupal.org/node/1905070

Tips on how to use this module to find schema issues at
http://drupal.org/node/1910624#comment-7088154
