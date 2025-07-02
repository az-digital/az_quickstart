<?php

declare(strict_types=1);

namespace Drupal\masquerade_log;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters the active loggers in order to log also the original user.
 */
class MasqueradeLogServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Iterate over all active loggers and decorate them with a class that is
    // aware of the masquerade status of the current user.
    foreach (
      $container->findTaggedServiceIds('logger') as $service_id => $attributes) {
      $original = $container->getDefinition($service_id);
      // Remove the 'logger' tag from the original service, so that it won't get
      // collected by the LoggerChannelFactory service collector. We'll tag the
      // decorator with the 'logger' tag later. in this method.
      $tags = $original->getTags();
      unset($tags['logger']);
      $original->setTags($tags);

      // Add the decorator service.
      $container
        ->register("{$service_id}.decorator", MasqueradeLogLogger::class)
        ->setTags(['logger' => []])
        ->setDecoratedService($service_id)
        ->setArguments([
          new Reference("{$service_id}.decorator.inner"),
          new Reference('session'),
          new Reference('entity_type.manager'),
        ]);
    }
  }

}
