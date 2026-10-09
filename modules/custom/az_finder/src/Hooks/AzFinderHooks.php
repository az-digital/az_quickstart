<?php

declare(strict_types=1);

namespace Drupal\az_finder\Hooks;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for az_finder.
 */
class AzFinderHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Only act on Views UI edit display form.
    if ($form_id !== 'views_ui_edit_display_form') {
      return;
    }

    // Only act if this view uses our custom exposed form plugin.
    $view = $form_state->get('view');
    if (!$view) {
      return;
    }

    $display_id = $form_state->get('display_id');
    $executable = $view->getExecutable();

    // Check if this display uses our plugin.
    if (!$executable->setDisplay($display_id)) {
      return;
    }

    $exposed_form = $executable->display_handler->getOption('exposed_form');
    if (!isset($exposed_form['type']) || $exposed_form['type'] !== 'az_better_exposed_filters') {
      return;
    }

    // Add our validation handler to run before core's validation.
    // This ensures the view is properly initialized.
    array_unshift($form['#validate'], [static::class, 'ensureDisplayInitialized']);
  }

  /**
   * Validation handler to ensure view display is properly initialized.
   */
  public static function ensureDisplayInitialized(array &$form, FormStateInterface $form_state): void {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');

    if ($view && $display_id) {
      $executable = $view->getExecutable();
      // Ensure the display is set, which initializes display_handler.
      $executable->setDisplay($display_id);
    }
  }

}
