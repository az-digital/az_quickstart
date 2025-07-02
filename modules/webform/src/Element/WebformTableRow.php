<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\webform\Utility\WebformFormHelper;

/**
 * Provides a render element for webform table row.
 *
 * @FormElement("webform_table_row")
 */
class WebformTableRow extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#optional' => FALSE,
      '#process' => [
        [$class, 'processTableRow'],
      ],
      '#pre_render' => [],
    ];
  }

  /**
   * Processes a webform table row element.
   */
  public static function processTableRow(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attributes']['class'][] = 'webform-table-row';
    if (!empty($element['#states'])) {
      WebformFormHelper::processStates($element);
    }
    return $element;
  }

}
