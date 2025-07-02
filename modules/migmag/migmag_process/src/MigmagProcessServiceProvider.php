<?php

declare(strict_types=1);

namespace Drupal\migmag_process;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for Migrate Magician Process Plugins module.
 */
class MigmagProcessServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register the 'migmag_process.lookup.stub' service only when its
    // 'plugin.manager.migration' service dependency is present.
    if ($container->has('plugin.manager.migration')) {
      $definition = new Definition(
        MigMagMigrateStub::class,
        [
          new Reference('plugin.manager.migration'),
        ]);
      $definition->setPublic(TRUE);
      $container->setDefinition('migmag_process.lookup.stub', $definition);
    }
  }

}
