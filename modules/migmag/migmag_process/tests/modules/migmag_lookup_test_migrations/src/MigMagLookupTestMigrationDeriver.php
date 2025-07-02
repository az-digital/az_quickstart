<?php

namespace Drupal\migmag_lookup_test_migrations;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Deriver for test migrations.
 */
class MigMagLookupTestMigrationDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We have to use sort derivative IDs because Drupal core's Sql id map
    // plugin is broken.
    // @see https://drupal.org/i/2845340
    return [
      's_3_3' => NestedArray::mergeDeepArray(
        [
          $base_plugin_definition,
          [
            'source' => [
              'data_rows' => [
                [
                  'derived_id' => 3,
                  'derived_subid' => 3,
                ],
              ],
            ],
          ],
        ],
        TRUE
      ),
      's_1_1' => NestedArray::mergeDeepArray(
        [
          $base_plugin_definition,
          [
            'source' => [
              'data_rows' => [
                [
                  'derived_id' => 1,
                  'derived_subid' => 1,
                ],
              ],
            ],
          ],
        ],
        TRUE
      ),
    ];
  }

}
