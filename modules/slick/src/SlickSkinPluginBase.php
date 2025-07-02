<?php

namespace Drupal\slick;

// @todo use Drupal\blazy\Plugin\SkinPluginBase;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base class for all slick skins.
 *
 * @todo extends SkinPluginBase
 */
abstract class SlickSkinPluginBase extends PluginBase implements SlickSkinPluginInterface {

  /**
   * The slick main/thumbnail skin definitions.
   *
   * @var array
   */
  protected $skins;

  /**
   * The slick arrow skin definitions.
   *
   * @var array
   */
  protected $arrows;

  /**
   * The slick dot skin definitions.
   *
   * @var array
   */
  protected $dots;

  /**
   * The manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
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
    $instance->manager = $container->get('slick.manager');
    $instance->skins = $instance->setSkins();
    $instance->arrows = $instance->setArrows();
    $instance->dots = $instance->setDots();

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function skins() {
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function arrows() {
    return $this->arrows;
  }

  /**
   * {@inheritdoc}
   */
  public function dots() {
    return $this->dots;
  }

  /**
   * Alias for BlazyInterface::getPath().
   *
   * @todo add type hint after sub-modules: ?string
   */
  protected function getPath($type, $name) {
    return $this->manager->getPath($type, $name, TRUE);
  }

  /**
   * Sets the required plugin main/thumbnail skins.
   */
  abstract protected function setSkins();

  /**
   * Sets the optional/ empty plugin arrow skins.
   */
  protected function setArrows() {
    return [];
  }

  /**
   * Sets the optional/ empty plugin dot skins.
   */
  protected function setDots() {
    return [];
  }

}
