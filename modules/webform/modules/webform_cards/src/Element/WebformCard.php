<?php

namespace Drupal\webform_cards\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a card container.
 *
 * @RenderElement("webform_card")
 */
class WebformCard extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#prev_button_label' => '',
      '#next_button_label' => '',
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCard'],
        [$class, 'preRenderGroup'],
      ],
      '#value' => NULL,
      '#theme_wrappers' => ['webform_card'],
    ];
  }

  /**
   * Adds form element theming to webform card.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   webform card.
   *
   * @return array
   *   The modified element.
   */
  public static function preRenderWebformCard(array $element) {
    $element['#attributes']['data-title'] = $element['#title'];
    if (!empty($element['#webform_key'])) {
      $element['#attributes']['data-webform-key'] = $element['#webform_key'];
    }
    if (!empty($element['#prev_button_label'])) {
      $element['#attributes']['data-prev-button-label'] = $element['#prev_button_label'];
    }
    if (!empty($element['#next_button_label'])) {
      $element['#attributes']['data-next-button-label'] = $element['#next_button_label'];
    }
    return $element;
  }

}
