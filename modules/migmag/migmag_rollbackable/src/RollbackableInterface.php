<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable;

/**
 * Rollbackable destination interface.
 *
 * Shim for our destination plugin replacements which make non-rollbackable
 * migrations rollbackable.
 */
interface RollbackableInterface {

  /**
   * The rollback data table name.
   *
   * @const string
   */
  const ROLLBACK_DATA_TABLE = 'migmag_rollbackable_data';

  /**
   * The rollback state table name.
   *
   * @const string
   */
  const ROLLBACK_STATE_TABLE = 'migmag_rollbackable_new_targets';

  /**
   * Plugin ID column name.
   *
   * @const string
   */
  const ROLLBACK_MIGRATION_PLUGIN_ID_COL = 'migration_plugin_id';

  /**
   * Config ID column name.
   *
   * @const string
   */
  const ROLLBACK_TARGET_ID_COL = 'target_id';

  /**
   * Langcode column name.
   *
   * @const string
   */
  const ROLLBACK_LANGCODE_COL = 'langcode';

  /**
   * Rollback data column name.
   *
   * @const string
   */
  const ROLLBACK_DATA_COL = 'rollback_data';

  /**
   * Name of the component column name.
   *
   * @const string
   */
  const ROLLBACK_COMPONENT_COL = 'component';

}
