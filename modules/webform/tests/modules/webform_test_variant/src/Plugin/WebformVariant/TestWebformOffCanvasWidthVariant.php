<?php

namespace Drupal\webform_test_variant\Plugin\WebformVariant;

use Drupal\webform\Plugin\WebformVariantBase;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Webform variant off-canvas width.
 *
 * @WebformVariant(
 *   id = "test_offcanvas_width",
 *   label = @Translation("Test off-canvas width"),
 *   category = @Translation("Test"),
 *   description = @Translation("Test of a webform variant off-canvas width."),
 * )
 */
class TestWebformOffCanvasWidthVariant extends WebformVariantBase {

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasWidth() {
    return WebformDialogHelper::DIALOG_WIDE;
  }

}
