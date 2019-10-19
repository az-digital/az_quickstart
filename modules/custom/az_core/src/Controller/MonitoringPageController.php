<?php

namespace Drupal\az_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class MonitoringPageController.
 */
class MonitoringPageController extends ControllerBase {

  /**
   * Deliver.
   *
   * @return array
   *   Return monitoring page render array.
   */
  public function deliver() {
    return [
      '#type' => 'markup',
      '#markup' => '<p>This page is intended for use with uptime monitoring tools.</p>',
      '#attached' => [
        'http_header' => [
          ['X-Robots-Tag', 'none'],
        ],
      ],
    ];
  }

}
