# Config Filter

Modules such as Configuration Split want to modify the configuration when it is
synchronized between the database and the exported yaml files.
This module provides the API to do so but does not influence a sites operation.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/config_filter).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/config_filter).


## How it works

Configuration Filter 1.x swaps the `config.storage.sync` service from Drupal 8 core.
Configuration Filter 2.x applies the filters when the config storage transformation api is used.
This means that modules which use Configuration Filter work with the API added to Drupal 8.8.
Modules depending on Configuration Filter can depend on both 1.x or 2.x as the Configuration Filter API is the same.


## What is a ConfigFilter

The API config filter provides has been superseeded by the config transformation
API in Drupal core, added in Drupal 8.8. Modules should transition to the core API.

A ConfigFilter is a plugin. This module provides the plugin definition, the
plugin manager and the storage factory.
A ConfigFilter can have the following annotation:

```php
/**
 * @ConfigFilter(
 *   id = "may_plugin_id",
 *   label = @Translation("An example configuration filter"),
 *   weight = 0,
 *   status = TRUE,
 *   storages = {"config.storage.sync"},
 * )
 */
```
See `\Drupal\config_filter\Annotation\ConfigFilter`.

The weight allows the filters to be sorted. The status allows the filter to be
active or inactive, the `ConfigFilterManagerInterface::getFiltersForStorages`
will only take active filters into consideration. The weight, status and
storages are optional and the above values are the default.


## Alternative Config Filter Managers

Plugins are only available from enabled modules. If you want to provide a
config filter from a php library, all you have to do is implement the
`\Drupal\config_filter\ConfigFilterManagerInterface` and add it to the
service container with a `config.filter` tag.
Services with higher priority will have their filters added first.


## Requirements

This module requires no modules outside of Drupal core.


## Installation

You probably do not need to install Configuration Filter.
Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no menu or modifiable settings. There is no configuration.
Configuration Filter is an API-only module.


## Maintainers

- Fabian Bircher - [bircher](https://www.drupal.org/u/bircher)
