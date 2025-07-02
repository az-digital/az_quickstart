<?php

namespace Drupal\migmag_target_bundle_test;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Deriver for test migrations.
 */
class MigMagTargetBundleDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // We have to use sort derivative IDs because Drupal core's Sql ID map
    // plugin is broken.
    // @see https://drupal.org/i/2845340
    return [
      'derivative_1' => NestedArray::mergeDeepArray(
        [
          $base_plugin_definition,
          [
            'source' => [
              'data_rows' => [
                [
                  'id' => 'derivative 1 vocab 1',
                ],
                [
                  'id' => 'derivative 1 vocab 2',
                ],
              ],
            ],
          ],
        ],
        TRUE
      ),
      'derivative_2' => NestedArray::mergeDeepArray(
        [
          $base_plugin_definition,
          [
            'source' => [
              'data_rows' => [
                [
                  'id' => 'derivative 2 vocab',
                ],
              ],
            ],
          ],
        ],
        TRUE
      ),
      'id_collision' => NestedArray::mergeDeepArray(
        [
          $base_plugin_definition,
          [
            'source' => [
              'note' => "Source IDs should conflict with 'migmag_tbt_vocabulary'",
              'data_rows' => [
                [
                  'id' => 'vocabulary 1',
                ],
                [
                  'id' => 'vocabulary 2',
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
