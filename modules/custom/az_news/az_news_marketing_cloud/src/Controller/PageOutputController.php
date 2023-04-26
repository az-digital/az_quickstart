<?php

namespace Drupal\az_news_marketing_cloud\Controller;

use Drupal\Core\Controller\ControllerBase;

class PageOutputController extends ControllerBase {

  /**
   * Returns a render-able array for a test page.
   */
  public function template() {

    return array (
      '#theme' => 'html__export__marketing_cloud',
    );

  }

}
