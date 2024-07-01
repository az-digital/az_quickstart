<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class AZFinderOverrides for managing overrides.
 */
final class AZFinderOverrides {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * AZFinderOverrides constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Get existing overrides.
   */
  public function getExistingOverrides() {
    $config_names = $this->configFactory->listAll('az_finder.tid_widget.');
    $overrides = [];

    foreach ($config_names as $config_name) {
      $view_id_display_id = substr($config_name, strlen('az_finder.tid_widget.'));
      [$view_id, $display_id] = explode('.', $view_id_display_id);

      // Get the specific display config.
      $view_config = $this->configFactory->get('views.view.' . $view_id);
      $display_options = $view_config->get('display.' . $display_id . '.display_options') ?? [];

      // Check if 'filters' is set in display-specific options.
      if (empty($display_options['filters']) && isset($view_config->get('display')['default']['display_options']['filters'])) {
        $default_filters = $view_config->get('display')['default']['display_options']['filters'];
        $display_options['filters'] = $default_filters;
      }

      $overrides[$view_id . '_' . $display_id] = [
        'view_id' => $view_id,
        'display_id' => $display_id,
        'vocabularies' => $display_options['filters']['vocabularies'] ?? [],
        'origin' => 'config',
      ];
    }

    return $overrides;
  }

}
