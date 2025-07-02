# CONTENTS OF THIS FILE

 - Introduction
 - Requirements
 - Configuration
 - Maintainers
 - Supporting organizations

## Introduction

Provides the ability to create cron migrations (configuration entities) with a
reference towards migration entities in order to import them during cron runs.
You can also define additional options such as update, sync and ignore
dependencies for each of the referenced migrations.

The cron migration entities are exportable, meaning that you can deploy them to
other environments/projects. Another use case would be to isolate specific cron
migrations using the Config Split module.

## Requirements

- Migrate Tools module, which also creates a dependency towards Migrate Plus
  and Core Migrate module.

## Configuration

 - Module ships with a simple configuration UI which allows you to create, edit
   and delete cron migrations. Navigate to
   `/admin/config/migrate_queue_importer/cron_migration` to use the UI.

## Maintainers

 - Dumitru Postovan (@postovan-dumitru) https://drupal.org/u/postovan-dumitru
 - David Baetge (@daveiano) https://www.drupal.org/u/daveiano
 - Chris Green (@trackleft2) https://www.drupal.org/u/trackleft2

## Supporting organizations

 - Axis Communications AB - The company is the initial sponsor for the module.
 - The University of Arizona - Drupal 11 compatibility and ongoing maintenance.
