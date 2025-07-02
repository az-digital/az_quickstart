<?php

namespace Drupal\webform_test_handler\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Webform submission test off-canvas width handler.
 *
 * @WebformHandler(
 *   id = "test_offcanvas_width",
 *   label = @Translation("Test off-canvas width"),
 *   category = @Translation("Testing"),
 *   description = @Translation("Tests handler off-canvas width.")
 * )
 */
class TestWebformOffCanvasWidthHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasWidth() {
    return WebformDialogHelper::DIALOG_WIDE;
  }

}
