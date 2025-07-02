<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\views\Views;

/**
 * A derivative class which sets the wizard for remote data integrations.
 */
final class ViewsWizardDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $views_data = Views::viewsData();
    $valid_bases = array_filter($views_data->getAll(), static function (array $data): bool {
      $query_id = $data['table']['base']['query_id'] ?? '';
      return $query_id === 'views_remote_data_query';
    });
    $this->derivatives = [];
    foreach ($valid_bases as $table => $views_info) {
      // Replace the default wizard with the derivative.
      $wizard_id = $views_info['table']['wizard_id'] ?? '';
      if ($wizard_id === 'views_remote_data') {
        $this->derivatives[$table] = [
          'base_table' => $table,
          'title' => $views_info['table']['base']['title'],
        ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
