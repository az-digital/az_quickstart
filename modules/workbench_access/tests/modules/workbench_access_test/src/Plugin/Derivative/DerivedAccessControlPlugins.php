<?php

namespace Drupal\workbench_access_test\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Defines a class for deriving plugins for test-sake.
 */
class DerivedAccessControlPlugins extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];
    foreach (['foo', 'bar'] as $plugin_id) {
      $this->derivatives[$plugin_id] = [
        'label' => sprintf('Plugin for %s', $plugin_id),
      ] + $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
