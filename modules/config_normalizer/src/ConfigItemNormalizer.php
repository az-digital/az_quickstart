<?php

namespace Drupal\config_normalizer;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class responsible for performing configuration normalization.
 */
class ConfigItemNormalizer implements ConfigItemNormalizerInterface {

  /**
   * The configuration normalizer plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $normalizerManager;

  /**
   * Local cache for normalizer instances.
   *
   * @var array
   */
  protected $normalizers;

  /**
   * Constructs a new ConfigItemNormalizer object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $normalizer_manager
   *   The configuration normalizer plugin manager.
   */
  public function __construct(PluginManagerInterface $normalizer_manager) {
    $this->normalizerManager = $normalizer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($name, array $data, array $context = []) {
    $normalizers = $this->normalizerManager->getDefinitions();
    uasort(
      $normalizers,
      ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']
    );

    foreach (array_keys($normalizers) as $id) {
      $this->applyNormalizer($id, $name, $data, $context);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function applyNormalizer($id, $name, array &$data, array $context) {
    $normalizer = $this->getNormalizerInstance($id);
    $normalizer->normalize($name, $data, $context);
  }

  /**
   * Returns an instance of the specified package generation normalizer.
   *
   * @param string $id
   *   The string identifier of the normalizer to use to package
   *   configuration.
   *
   * @return \Drupal\config_normalizer\Plugin\ConfigNormalizerInterface
   *   The ConfigNormalizer instance.
   */
  protected function getNormalizerInstance($id) {
    if (!isset($this->normalizers[$id])) {
      $instance = $this->normalizerManager->createInstance($id);
      $this->normalizers[$id] = $instance;
    }

    return $this->normalizers[$id];
  }

}
