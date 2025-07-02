<?php

namespace Drupal\viewsreference;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\viewsreference\Normalizer\ViewsReferenceItemNormalizer;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Service Provider for Views Reference.
 */
class ViewsreferenceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // Add the views reference normalizer for the HAL module.
    if (isset($modules['hal'])) {
      $parent_definition = $container->getDefinition('serializer.normalizer.entity_reference_item.hal');
      $service_definition = new Definition(ViewsReferenceItemNormalizer::class, $parent_definition->getArguments());

      // The priority must be higher than that of
      // serializer.normalizer.entity_reference_item.hal in hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => $parent_definition->getTags()['normalizer'][0]['priority'] + 1]);
      $container->setDefinition('viewsreference.normalizer.views_reference_item.hal', $service_definition);
    }
  }

}
