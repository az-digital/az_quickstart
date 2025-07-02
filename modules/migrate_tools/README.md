# MIGRATE TOOLS

 * Introduction 

## Introduction
 The Migrate Tools module provides tools for running and managing Drupal
8 migrations.

Drush commands supported include:

 -   migrate-status - Lists migrations and their status.
 -   migrate-import - Performs import operations.
 -   migrate-rollback - Performs rollback operations.
 -   migrate-stop - Cleanly stops a running operation.
 -   migrate-reset-status - Sets a migration status to Idle if it's
    gotten stuck.
 -   migrate-messages - Lists any messages associated with a migration
    import.

 The UI at this point provides a front-end equivalent to the
migrate-status and migrate-messages commands. It will be enhanced to
allow running the other operations, as well as provide the ability to
create and alter migrations.
