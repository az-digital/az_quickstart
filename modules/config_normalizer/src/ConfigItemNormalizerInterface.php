<?php

namespace Drupal\config_normalizer;

/**
 * Defines an interface for config item normalizers.
 */
interface ConfigItemNormalizerInterface {

  /**
   * Normalizes config for comparison.
   *
   * Normalization can help ensure that config from different storages can be
   * compared meaningfully.
   *
   * @param string $name
   *   The name of a configuration object to normalize.
   * @param array $data
   *   Configuration array to normalize.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   *
   * @return array
   *   Normalized configuration array.
   */
  public function normalize($name, array $data, array $context = []);

}
