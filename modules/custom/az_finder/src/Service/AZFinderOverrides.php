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
  protected $configFactory;

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
      $config = $this->configFactory->get($config_name);
      $view_id_display_id = substr($config_name, strlen('az_finder.tid_widget.'));
      [$view_id, $display_id] = explode('.', $view_id_display_id);

      $overrides[$view_id . '_' . $display_id] = [
        'view_id' => $view_id,
        'display_id' => $display_id,
        'vocabularies' => $config->get('vocabularies') ?? [],
        'origin' => 'config',
      ];
    }

    return $overrides;
  }

}
