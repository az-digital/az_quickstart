Description:
============

The ExternalAuth module provides a generic service for logging in and
registering users that are authenticated against an external site or service and
storing the authentication details.
It is the Drupal 8+ equivalent of user_external_login_register() and related
functions, as well as the authmap table in Drupal 6 & 7 core.

Usage:
======

Install this module if it's required as dependency for an external
authentication Drupal module. Module authors that provide external
authentication methods can use this helper service to provide a consistent API
for storing and retrieving external authentication data.

Links / views support:
======================

admin/people/authmap shows a list of all "Authentication names" that are
registered by external authentication methods for each user login.

The list facilitates deleting links to 'wrong' / outdated authentication names.
There is explicitly no add/edit facility for these links; they should only be
added through logins (or, depending on the method, prepopulated by a system
administrator to allow a specific set of users to use login).

The list does not show the "provider" (i.e. the method) by default, because
in most situations, this is an internal name that is always the same and not
useful to administrators. If multiple authentication methods are active in your
site, the list could start showing duplicate entries, and you may need to
distinguish between the different methods by changing configuration, e.g. by:

* editing the view at admin/structure/views/view/authmap, and adding the
  "provider" field.
* denying access to the main view (admin/people/authmap) somehow, and only
  accessing lists specific to a provider (e.g. admin/people/authmap/samlauth)
* editing the view at admin/structure/views/view/authmap, and changing the
  "Contextual filter" to be "Display a summary". This shows the URLs for all
  appropriate provider-specific lists.

Authentication names can be added to any other views in your site that include
user information. These can then also start showing duplicate entries when
a user logs in through multiple authentication methods, which will need similar
adjustments as suggested above.

Installation:
=============

Installation of this module is just like any other Drupal module.

1) Download the module
2) Uncompress it
3) Move it to the appropriate modules directory (usually, /modules)
4) Go to the Drupal module administration page for your site
5) Enable the module

Upgrading:
==========

The Drupal 8+ version of this module provides Migrate functionality to upgrade
your Drupal 6 or Drupal 7 authmap entries to your Drupal 8 installation.

In order to upgrade the authmap table from your Drupal 6 or Drupal 7
installation, follow these instructions:
  - Install and enable the ExternalAuth module as described above.
  - Activate the Migrate and Migrate Drupal core modules and perform your
  upgrade migration.
  See https://www.drupal.org/upgrade/migrate for more information about this
  process.
