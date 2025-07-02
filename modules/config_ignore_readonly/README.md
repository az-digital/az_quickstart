# Config Ignore Readonly

The Config Ignore Readonly module bridges the Config Ignore and Config Readonly
modules to automatically whitelist configuration forms that are defined as
ignored configuration entities.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/config_ignore_readonly

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/config_ignore_readonly

## Requirements

This module requires the following modules:

* Config Ignore (https://www.drupal.org/project/config_ignore)
* Config Readonly (https://www.drupal.org/project/config_readonly)

## Installation

* Install as you would normally install a contributed Drupal module. Visit:
  https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
  for further information.

### Drupal 9

For Drupal 9 installations, please install the `2.x` version of the module.

* **Recommended:** Install with composer
  ```bash
  $ composer require drupal/config_ignore_readonly:^2.0
  ```

### Drupal 8

For Drupal 8 installations, please install the `1.x` version of the module.

* **Recommended:** Install with composer
  ```bash
  $ composer require drupal/config_ignore_readonly:^1.0
  ```

## Configuration

There is no configuration.

## Troubleshooting

* If the configuration form is still showing the readonly warning:
  * If a configuration form has multiple config names in the
    `getEditableConfigNames` method on the config form, all config names will
    have to be ignored for the form to be editable.
  * The pattern `~webform.webform.contact` (will force import for this
    configuration, even if ignored by a wildcard) in Config Ignore is **not**
    supported.
  * The pattern `user.mail:register_no_approval_required.body` (will ignore the
    body of the no approval required email setting, but will not ignore other
    user.mail configuration) in Config Ignore is **not** supported.

## Maintainers

* Nathan Dentzau (nathandentzau) - https://www.drupal.org/u/nathandentzau
* Marc Addeo (marcaddeo) - https://www.drupal.org/u/marcaddeo
