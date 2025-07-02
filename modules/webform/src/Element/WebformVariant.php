<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\WebformSubmissionForm;

/**
 * Provides a webform variant element.
 *
 * @FormElement("webform_variant")
 */
class WebformVariant extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#variant' => '',
      '#process' => [
        [$class, 'processWebformVariant'],
      ],
      '#pre_render' => [],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * #process callback for webform variant.
   */
  public static function processWebformVariant(&$element, FormStateInterface $form_state, &$complete_form) {
    $form_object = $form_state->getFormObject();
    if ($element['#value']) {
      $element['#children'] = $element['#value'];
      if ($form_object instanceof WebformSubmissionForm) {
        // Display variant label.
        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $form_object->getWebform();
        if ($webform->hasVariant($element['#value'])) {
          $variant_plugin = $webform->getVariant($element['#value']);
          $element['#children'] = $variant_plugin->label();
        }
      }
    }
    return $element;
  }

}
