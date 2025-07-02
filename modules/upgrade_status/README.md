# Upgrade Status

Review Drupal major upgrade readiness of the environment and components of the site.

The module provides the following key features:

- Checks if you are using a version of Drupal that supports an upgrade.
- Checks if your system meets the next major version's system requirements.
- Integrates with the Update Status core module to inform you to update your contributed projects. Projects can be compatible with multiple major Drupal versions, so most projects can be updated on your existing site before doing the core major update.
- Runs phpstan checks and a whole set of other checks to find any compatibility issues with the next Drupal major version that may remain.
- Integrates with drush for command line usage and to plug into CI systems.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/upgrade_status).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/upgrade_status).

## Requirements

This module requires no modules outside of Drupal core.

## Installation

You must use Composer to install all the required third party dependencies,
for example `composer require drupal/upgrade_status` then install as you would
normally install a contributed Drupal module. For further information, see:
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

While the module takes an effort to categorize projects properly, installing
[Composer Deploy](https://www.drupal.org/project/composer_deploy) or
[Git Deploy](https://www.drupal.org/project/git_deploy) as appropriate to your
Drupal setup is suggested to identify custom vs. contributed projects more
accurately and gather version information leading to useful available update
information.

## Configuration

There are no configuration options. Go to Administration » Reports » Upgrade
status to use the module.

## Maintainers

- Gábor Hojtsy - [Gábor Hojtsy](https://www.drupal.org/u/g%C3%A1bor-hojtsy)
