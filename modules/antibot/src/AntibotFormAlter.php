<?php

namespace Drupal\antibot;

use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Provides a trusted callback to alter Antibot form.
 */
class AntibotFormAlter implements RenderCallbackInterface {

  /**
   * Callback #pre_render: Alter forms.
   */
  public static function preRender($build) {
    // Add the Antibot library.
    $build['#attached']['library'][] = 'antibot/antibot.form';

    // Store the form ID that the JS can replace the action path along with the
    // form key.
    $form_id = $build['#id'];
    if (isset($build['#attributes']['id'])) {
      $form_id = $build['#attributes']['id'];
    }

    $build['#attached']['drupalSettings']['antibot']['forms'][$build['#id']] = [
      'id' => $form_id,
      'key' => $build['#antibot_key'],
    ];

    // Store the action placeholder as an attribute so that it converts
    // during the building of the form. This is needed because in Drupal 8
    // the form action is a placeholder that is not added until the very
    // last moment, in order to keep the form cacheable.
    $build['#attributes']['data-action'] = $build['#action'];

    // Change the action so the submission does not go through.
    $build['#action'] = base_path() . 'antibot';

    // Add a class to the form.
    $build['#attributes']['class'][] = 'antibot';

    return $build;
  }

}
