<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

class AZFinderOverrides {
  protected $configFactory;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

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
      ];
    }

    return $overrides;
  }
}
