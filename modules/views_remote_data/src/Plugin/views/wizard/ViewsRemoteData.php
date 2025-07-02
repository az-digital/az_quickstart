<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for remote data integrations.
 *
 * @ViewsWizard(
 *   id = "views_remote_data_standard",
 *   title = @Translation("Remote data wizard"),
 *   deriver = "Drupal\views_remote_data\Plugin\Derivative\ViewsWizardDeriver",
 * )
 */
final class ViewsRemoteData extends WizardPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions(): array {
    $display_options = parent::defaultDisplayOptions();
    // Default to `none` for result caching.
    $display_options['cache']['type'] = 'none';
    return $display_options;
  }

}
