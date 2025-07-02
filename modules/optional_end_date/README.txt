CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

Optional end date

Make the end date in a Date range field (DateRangeItem) optional.

An extra "Optional end date" checkbox is added to the Date range field type
Storage settings. When the box is checked, the end date is no longer required.

This will mimic the behavior of of optional end dates in Drupal 8.9.x. So when
8.9.x is ready, you should be able to uninstall this module after updating, and
use the core implementation instead.

See https://www.drupal.org/project/drupal/issues/2794481 for more information
about the core implementation.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

You need to check the "Optional end date" setting for all existing and new
daterange fields, where the end date is not required.
This is done in the "Storage settings" form of the daterange fields.


MAINTAINERS
-----------

* Birk (Birk) - https://www.drupal.org/u/birk
