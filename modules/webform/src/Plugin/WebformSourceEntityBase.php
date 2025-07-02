<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform source entity.
 *
 * @see \Drupal\webform\Plugin\WebformExporterInterface
 * @see \Drupal\webform\Plugin\WebformExporterManager
 * @see \Drupal\webform\Plugin\WebformExporterManagerInterface
 * @see plugin_api
 */
abstract class WebformSourceEntityBase extends PluginBase implements WebformSourceEntityInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
