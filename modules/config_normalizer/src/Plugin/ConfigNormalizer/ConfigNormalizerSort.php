<?php

namespace Drupal\config_normalizer\Plugin\ConfigNormalizer;

use Drupal\config_normalizer\Plugin\ConfigNormalizerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\Yaml\Inline;

/**
 * Recursively sorts a configuration array.
 *
 * @ConfigNormalizer(
 *   id = "sort",
 *   label = @Translation("sort"),
 *   weight = 20,
 *   description = @Translation("Recursively sorts a configuration array."),
 * )
 */
class ConfigNormalizerSort extends ConfigNormalizerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function normalize($name, array &$data, array $context) {
    // Only sort if the normalization mode is default.
    if ($this->isDefaultModeContext($context)) {
      // Recursively normalize and return.
      $data = $this->normalizeArray($data);
    }
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to normalize.
   *
   * @return array
   *   An array that is sorted by key, at each level of the array, with empty
   *   arrays removed.
   */
  protected function normalizeArray(array $array) {
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $new = $this->normalizeArray($value);
        if (count($new)) {
          $array[$key] = $new;
        }
      }
    }

    // If the array is associative, sort by key.
    if (Inline::isHash($array)) {
      ksort($array);
    }
    // Otherwise, sort by value.
    else {
      sort($array);
    }

    return $array;
  }

}
