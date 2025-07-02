<?php

namespace Drupal\config_readonly;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides CMI storage.
 */
class ConfigReadonlyServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {}

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->getParameter('kernel.environment') !== 'install') {
      $definition = $container->getDefinition('config.storage');
      $definition->setClass('Drupal\config_readonly\Config\ConfigReadonlyStorage');
      $definition->setArguments(
        [
          new Reference('config.storage.active'),
          new Reference('cache.config'),
          new Reference('lock'),
          new Reference('request_stack'),
          new Reference('module_handler'),
        ]
      );
    }
  }

}
