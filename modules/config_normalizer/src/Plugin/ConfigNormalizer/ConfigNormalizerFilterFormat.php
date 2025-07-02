<?php

namespace Drupal\config_normalizer\Plugin\ConfigNormalizer;

use Drupal\config_normalizer\Plugin\ConfigNormalizerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Normalizes filter_format config entity data.
 *
 * @ConfigNormalizer(
 *   id = "filter_format",
 *   label = @Translation("Filter format"),
 *   weight = 20,
 *   description = @Translation("Removes the roles element from filter formats, since this element is valid only on exported configuration."),
 * )
 */
class ConfigNormalizerFilterFormat extends ConfigNormalizerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($name, array &$data, array $context) {
    // In 'prepare' context we don't change data that's needed at write time.
    if ($this->isDefaultModeContext($context) && $filter_format = $this->entityTypeManager->getDefinition('filter_format', FALSE)) {
      $prefix = $filter_format->getConfigPrefix();

      // The "roles" element from filter formats is valid only on exported
      // configuration.
      if (strpos($name, $prefix . '.') === 0) {
        unset($data['roles']);
      }
    }
  }

}
