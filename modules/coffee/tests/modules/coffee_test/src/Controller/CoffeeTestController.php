<?php

namespace Drupal\coffee_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;

class CoffeeTestController extends ControllerBase {

  public function csrf() {
    return new HtmlResponse('ok');
  }
}
