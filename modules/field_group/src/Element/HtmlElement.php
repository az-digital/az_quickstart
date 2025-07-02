<?php

namespace Drupal\field_group\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;

/**
 * Provides a render element for a html element.
 *
 * @FormElement("field_group_html_element")
 */
class HtmlElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processHtmlElement'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['field_group_html_element'],
    ];
  }

  /**
   * Process a html element.
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
  public static function processHtmlElement(array &$element, FormStateInterface $form_state) {

    // If an effect is set, we need to load extra js.
    if (!empty($element['#effect']) && $element['#effect'] !== 'none') {

      $element['#attached']['library'][] = 'field_group/formatter.html_element';
      $element['#attached']['library'][] = 'field_group/core';

      // Add the required classes for the js.
      $classes = [
        'field-group-html-element',
        'fieldgroup-collapsible',
        'effect-' . $element['#effect'],
      ];
      if (!empty($element['#speed'])) {
        $classes[] = 'speed-' . $element['#speed'];
      }
      if ($element['#attributes'] instanceof Attribute) {
        $element['#attributes']->addClass($classes);
      }
      else {
        $element['#attributes']['classes'] = array_merge($element['#attributes']['classes'], $classes);
      }

    }

    return $element;
  }

}
