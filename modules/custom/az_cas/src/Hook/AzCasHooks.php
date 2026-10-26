<?php

namespace Drupal\az_cas\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;

/**
 * Hook implementations for az_cas.
 */
class AzCasHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Modify the user login form to put CAS login below Drupal login.
   *
   * Runs last so the CAS login link added by the cas module is present.
   */
  #[Hook('form_user_login_form_alter', order: Order::Last)]
  public function formUserLoginFormAlter(array &$form, FormStateInterface $form_state): void {
    $cas_config = \Drupal::config('cas.settings');
    $az_cas_config = \Drupal::config('az_cas.settings');

    // Cached form must be busted if we alter AZ CAS settings.
    $form['#cache']['tags'] = array_merge($form['#cache']['tags'], $az_cas_config->getCacheTags());

    // Adjust the weight of the CAS login link (if enabled).
    $login_link_enabled = $cas_config->get('login_link_enabled');
    if ($login_link_enabled) {
      $form['cas_login_link']['#weight'] = 100;
    }

    $disable_login_form = $az_cas_config->get('disable_login_form');
    if ($disable_login_form) {
      $form_elements = ['name', 'pass', 'actions'];
      foreach ($form_elements as $element) {
        $form[$element]['#access'] = FALSE;
      }
    }
  }

}
