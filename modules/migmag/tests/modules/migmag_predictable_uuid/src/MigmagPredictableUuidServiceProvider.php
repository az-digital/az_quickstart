<?php

namespace Drupal\migmag_predictable_uuid;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Changes the UUID service to a generator with predictable results.
 */
class MigmagPredictableUuidServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('uuid')) {
      $container->getDefinition('uuid')
        ->setClass(PredictableUuid::class)
        ->addArgument(new Reference('state'))
        ->addArgument(new Reference('file_system'));
    }
  }

}
