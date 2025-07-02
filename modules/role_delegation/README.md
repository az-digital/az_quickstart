CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module allows site administrators to grant specific roles the authority
to assign selected roles to users, without them needing
the administer permissions permission.

For each role, Role Delegation provides a new assign ROLE role permission to
allow the assignment of that role.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/role_delegation

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/role_delegation


REQUIREMENTS
------------

No special requirements.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 1. Go to /admin/people/permissions and notice that for each role,
   Role Delegation provides a new assign ROLE role permission to
   allow the assignment of that role.
 2. If an administrator has one of the the assign {{ role }} role permissions
   or the assign all roles permission, a role assignment widget gets displayed
   in the account creation or editing form, and bulk add/remove role operations
   become available on the user administration page.

MAINTAINERS
-----------

Current maintainers:

 * Jeroen Tubex - https://www.drupal.org/u/jeroent
 * Ben Dougherty - https://www.drupal.org/u/benjy
