INTRODUCTION
------------
The migrate_example_advanced module demonstrates some techniques for Drupal 8+
migrations beyond the basics in migrate_example. It includes a group of
migrations with a wine theme.

SETUP
-----
To demonstrate XML migrations as realistically as possible, the setup module
provides the source data as REST services. So the migrations' references to
these services can be set up accurately, if you install migrate_example_advanced
via drush be sure to use the --uri parameter, e.g.

drush en -y migrate_example_advanced --uri=http://d8.local:8083/

THE WINE SITE
-------------
In this scenario, we have a wine aficionado site which stores data in SQL tables
as well is pulling in additional data from XML services.

To make the example as simple as to run as possible, the SQL data is placed in
tables directly in your Drupal database - in most real-world scenarios, your
source data will be in an external database. The migrate_example_advanced_setup
submodule creates and populates these tables, as well as configuring your Drupal
8 site (creating node types, vocabularies, fields, etc.) to receive the data,
and providing service endpoints for XML data.

STRUCTURE
---------
As with most custom migrations, there are two primary components to this
example:

1. Migration configuration, in the config/install directory. These YAML files
   describe the migration process and provide the mappings from the source data
   to Drupal's destination entities.

2. Source plugins, in src/Plugin/migrate/source. These are referenced from the
   configuration files, and provide the source data to the migration processing
   pipeline, as well as manipulating that data where necessary to put it into
   a canonical form for migrations.

UNDERSTANDING THE MIGRATIONS
----------------------------
Basic techniques demonstrated in the migrate_example module are not rehashed
here - it is expected that if you are learning Drupal 8+ migration, you will
study and understand those examples first, and use migrate_example_advanced to
learn about specific techniques beyond those basics. This example doesn't have
the narrative form of migrate_example - it's more of a grab-bag demonstrating
varous features, and is more of a reference for, say, copying the code to set
up an XML migration. An index of things demonstrated by this module:

Multiple vocabularies populated in one migration
------------------------------------------------
See migrate_plus.migration.wine_terms.yml.

Importing from XML services
---------------------------
See migrate_plus.migration.wine_role_xml.yml.
