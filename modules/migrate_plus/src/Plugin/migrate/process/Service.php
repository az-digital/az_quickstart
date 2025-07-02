<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\process\Callback;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin to use a callable from a service class.
 *
 * Available configuration keys:
 * - service: The ID of the service (e.g. file.mime_type.guesser).
 * - method: The name of the service public method.
 * All options for the callback plugin can be used, except for 'callable',
 * which will be ignored.
 *
 * Since Drupal 9.2.0, it is possible to supply multiple arguments using
 * unpack_source property. See: https://www.drupal.org/node/3205079
 *
 * Examples:
 *
 * @code
 * process:
 *   filemime:
 *     plugin: service
 *     service: file.mime_type.guesser
 *     method: guessMimeType
 *     source: filename
 * @endcode
 *
 * @code
 * source:
 *   # plugin ...
 *   constants:
 *     langcode: en
 *     slash: /
 * process:
 *   transliterated_value:
 *     plugin: service
 *     service: transliteration
 *     method: transliterate
 *     unpack_source: true
 *     source:
 *       - original_value
 *       - constants/langcode
 *       - constants/slash
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\migrate\process\Callback
 *
 * @MigrateProcessPlugin(
 *   id = "service"
 * )
 */
class Service extends Callback implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    if (!isset($configuration['service'])) {
      throw new \InvalidArgumentException('The "service" must be set.');
    }
    if (!isset($configuration['method'])) {
      throw new \InvalidArgumentException('The "method" must be set.');
    }
    if (!$container->has($configuration['service'])) {
      throw new \InvalidArgumentException(sprintf('You have requested the non-existent service "%s".', $configuration['service']));
    }
    $service = $container->get($configuration['service']);
    if (!method_exists($service, $configuration['method'])) {
      throw new \InvalidArgumentException(sprintf('The "%s" service has no method "%s".', $configuration['service'], $configuration['method']));
    }

    $configuration['callable'] = [$service, $configuration['method']];
    return new static($configuration, $plugin_id, $plugin_definition);
  }

}
