<?php

namespace Drupal\menu_block_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for menu_block_test routes.
 */
class MenuBlockTestController extends ControllerBase {

  /**
   * Returns placeholder page content which can be used for testing.
   *
   * @return string
   *   A string that can be used for comparison.
   */
  public function menuBlockTestCallback() {
    return ['#markup' => 'This is the menuBlockTestCallback() content.'];
  }

}
