<?php

namespace Drupal\field_group_accordion\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an accordion.
 *
 * @FormElement("field_group_accordion")
 */
class Accordion extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processAccordion'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['field_group_accordion'],
    ];
  }

  /**
   * Process the accordion item.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   details element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The processed element.
   */
  public static function processAccordion(array &$element, FormStateInterface $form_state) {

    // Add the jQuery UI accordion.
    $element['#attached']['library'][] = 'field_group_accordion/formatter.accordion';

    // Add the effect class.
    if (isset($element['#effect'])) {
      if (!isset($element['#attributes']['class'])) {
        $element['#attributes']['class'] = [];
      }
      $element['#attributes']['class'][] = 'effect-' . $element['#effect'];
    }

    return $element;
  }

}
