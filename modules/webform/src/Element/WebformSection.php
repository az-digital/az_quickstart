<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for a section/group of form elements.
 *
 * @RenderElement("webform_section")
 */
class WebformSection extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [
        [$class, 'processGroup'],
        [$class, 'processAjaxForm'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#value' => NULL,
      '#title_tag' => 'h2',
      // Must set default description display to before to prevent it from being
      // set to after.
      // @see \Drupal\Core\Form\FormBuilder::doBuildForm
      '#description_display' => 'before',
      '#theme_wrappers' => ['webform_section'],
    ];
  }

}
