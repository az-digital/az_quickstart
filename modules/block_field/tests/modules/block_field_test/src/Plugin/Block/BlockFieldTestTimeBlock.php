<?php

namespace Drupal\block_field_test\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Block field test time' block.
 *
 * @Block(
 *   id = "block_field_test_time",
 *   admin_label = @Translation("The time is..."),
 *   category = @Translation("Block field test")
 * )
 */
class BlockFieldTestTimeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'markup',
      '#markup' => date('H:i:s') . ' (' . time() . ')',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
