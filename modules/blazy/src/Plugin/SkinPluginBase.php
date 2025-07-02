<?php

namespace Drupal\blazy\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base class for all blazy skins.
 */
abstract class SkinPluginBase extends PluginBase implements SkinPluginInterface {

  /**
   * The blazy skin definitions.
   *
   * @var array
   */
  protected $skins;

  /**
   * The manager service.
   *
   * @var \Drupal\blazy\BlazyInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    // @todo at 3.x: $instance->manager = $container->get('blazy');
    $instance->manager = $container->get('blazy.manager');
    $instance->skins = $instance->setSkins();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->skins;
  }

  /**
   * Alias for BlazyInterface::getPath().
   */
  protected function getPath($type, $name, $absolute = TRUE): ?string {
    return $this->manager->getPath($type, $name, $absolute);
  }

  /**
   * Sets the required plugin skins.
   */
  abstract protected function setSkins();

}
