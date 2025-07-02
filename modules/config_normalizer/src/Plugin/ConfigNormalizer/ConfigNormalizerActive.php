<?php

namespace Drupal\config_normalizer\Plugin\ConfigNormalizer;

use Drupal\config_normalizer\Plugin\ConfigNormalizerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Normalizes configuration potentially saved to the active storage.
 *
 * @ConfigNormalizer(
 *   id = "active",
 *   label = @Translation("Active"),
 *   weight = 0,
 *   description = @Translation("Copies over properties that are set by core when configuration is saved to the active storage."),
 * )
 */
class ConfigNormalizerActive extends ConfigNormalizerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($name, array &$data, array $context) {
    if ($this->isActiveStorageContext($context) && ($active_data = $context['reference_storage_service']->read($name))) {
      // system.site.uuid may be set but empty.
      if (isset($data['uuid']) && empty($data['uuid'])) {
        unset($data['uuid']);
      }

      // Merge in uuid and _core while retaining the key order.
      $merged = array_replace($active_data, $data);
      $data = array_intersect_key(
        $merged,
        array_flip(array_merge(array_keys($data), ['uuid', '_core']))
      );
    }
  }

}
