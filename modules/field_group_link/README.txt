CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
This module provides a field group display formatter that groups containing
fields within an <a/> tag.

The destination of the link can be set from:
 * A link field
 * An entity reference field
 * A file field
 * The page of the current entity (useful for teasers) - whenever the entity
   type supports this.
 * A custom url (with support for token replacement).

Most entity types should be supported.  If you find an entity type that does not
work, please report an issue at:
https://drupal.org/project/issues/field_group_link

NOTE: You must ensure the output markup is valid HTML.  For example, do not
place link fields inside a field group link (as this would result in a nested
link).  It is recommended that you do not place text area fields inside (without
using a text format that would ensure contents are valid for an anchor tag).

REQUIREMENTS
------------
This module requires the Field Group module (https://drupal.org/project/field_group)

INSTALLATION
------------
 * Install as usual, see https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------
 * To create a field group link, edit the display of any entity (by clicking
   "Manage Display").
 * Add a new group and select the "Link" format.
 * Select the destination of the link by editing the field group configuration.

MAINTAINERS
-----------
Current maintainers:
 * Leon Kessler (leon.nk) - http://drupal.org/user/595374
