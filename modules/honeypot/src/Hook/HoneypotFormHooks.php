<?php

declare(strict_types=1);

namespace Drupal\honeypot\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\honeypot\HoneypotServiceInterface;

/**
 * Hook implementations used to alter and enhance forms.
 */
final class HoneypotFormHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new HoneypotFormHooks service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\honeypot\HoneypotServiceInterface $honeypot
   *   The honeypot service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    TranslationInterface $string_translation,
    protected HoneypotServiceInterface $honeypot,
  ) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_system_performance_settings_form_alter')]
  public function systemPerformanceSettingsFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $config = $this->configFactory->get('honeypot.settings');
    // If time-based protection is effectively disabled, no need for a warning.
    if ($config->get('time_limit') === 0) {
      return;
    }

    // Add a warning about caching on the Performance settings page.
    $description = '';
    if (!empty($form['caching']['page_cache_maximum_age']['#description'])) {
      // If there's existing description on 'caching' field, append a break to
      // it so that our verbiage is on its own line.
      $description .= $form['caching']['page_cache_maximum_age']['#description'] . '<br />';
    }

    $description .= $this->t('<em>Page caching may be disabled on any pages where a form is present due to the <a href=":url">Honeypot module\'s configuration</a>.</em>', [
      ':url' => Url::fromRoute('honeypot.config')->toString(),
    ]);

    $form['caching']['page_cache_maximum_age']['#description'] = $description;
  }

  /**
   * Implements hook_form_alter().
   *
   * Adds Honeypot features to forms enabled in the Honeypot admin interface.
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    // Don't use for maintenance mode forms (install, update, etc.).
    if (defined('MAINTENANCE_MODE')) {
      return;
    }

    // Add a tag to all forms, so that if they are cached and honeypot
    // configuration is changed, the cached forms are invalidated and honeypot
    // protection can be re-evaluated.
    $form['#cache']['tags'][] = 'config:honeypot.settings';

    // Get settings to determine which forms are unprotected and whether to
    // protect all forms.
    $config = $this->configFactory->get('honeypot.settings');
    $unprotected_forms = $config->get('unprotected_forms');
    $protect_all_forms = $config->get('protect_all_forms');

    // If configured to protect all forms, add protection to every form.
    if ($protect_all_forms && !in_array($form_id, $unprotected_forms)) {
      // Don't protect system forms - only admins should have access, and system
      // forms may be programmatically submitted by drush and other modules.
      if (preg_match('/[^a-zA-Z]system_/', $form_id) === 0 && preg_match('/[^a-zA-Z]search_/', $form_id) === 0 && preg_match('/[^a-zA-Z]views_exposed_form_/', $form_id) === 0) {
        $this->honeypot->addFormProtection($form, $form_state, ['honeypot', 'time_restriction']);
      }
    }
    // Otherwise add form protection only to the admin-configured forms.
    elseif (in_array($form_id, $this->honeypot->getProtectedForms())) {
      // The $form_id of the form we're currently altering is found
      // in the list of protected forms.
      $this->honeypot->addFormProtection($form, $form_state, ['honeypot', 'time_restriction']);
    }
  }

}
