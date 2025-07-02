<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform element for radio buttons with an other option.
 *
 * @FormElement("webform_radios_other")
 */
class WebformRadiosOther extends WebformOtherBase {

  /**
   * {@inheritdoc}
   */
  protected static $type = 'radios';

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Remove 'webform_' prefix from type.
    $type = str_replace('webform_', '', static::$type);

    // Unset #default_value that is not a valid #option.
    //
    // This behavior is needed when a radios #default_value was previously set
    // but now the radios are unchecked via conditional logic. This results in
    // nothing being posted back to the server, and the #default_value is used
    // which throws "An illegal choice has been detected." error.
    if ($input
      && !isset($input[$type])
      && isset($element['#default_value'])
      && !isset($element['#option'][$element['#default_value']])) {
      unset($element['#default_value']);
    }

    return parent::valueCallback($element, $input, $form_state);
  }

}
