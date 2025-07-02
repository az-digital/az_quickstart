<?php

namespace Drupal\upgrade_status_test_error\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test class which contains deprecation error.
 */
class UpgradeStatusTestErrorController extends ControllerBase {

  public function content() {
    upgrade_status_test_contrib_error_function_9_to_10();
  }

}
