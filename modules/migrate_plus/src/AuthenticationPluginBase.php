<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base authentication implementation.
 *
 * @see \Drupal\migrate_plus\Annotation\Authentication
 * @see \Drupal\migrate_plus\AuthenticationPluginInterface
 * @see \Drupal\migrate_plus\AuthenticationPluginManager
 * @see plugin_api
 */
abstract class AuthenticationPluginBase extends PluginBase implements AuthenticationPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
