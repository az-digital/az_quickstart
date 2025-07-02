<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformMarkup;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Provides a 'webform_test_offcanvas_width_element' element.
 *
 * @WebformElement(
 *   id = "webform_test_offcanvas_width_element",
 *   label = @Translation("Test element off-canvas width"),
 *   description = @Translation("Provides a form element for testing offcanvas width.")
 * )
 */
class WebformTestOffCanvasWidthElement extends WebformMarkup {

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasWidth() {
    return WebformDialogHelper::DIALOG_WIDE;
  }

}
