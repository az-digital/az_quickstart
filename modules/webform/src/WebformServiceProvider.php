<?php

namespace Drupal\webform;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\webform\Normalizer\WebformEntityReferenceItemNormalizer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Webform.
 */
class WebformServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['hal'])) {
      $service_definition = new Definition(WebformEntityReferenceItemNormalizer::class, [
        new Reference('hal.link_manager'),
        new Reference('serializer.entity_resolver'),
      ]);
      $service_definition->setPublic(TRUE);
      // The priority must be higher than that of
      // serializer.normalizer.entity_reference_item.hal in
      // hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 20]);
      $container->setDefinition('serializer.normalizer.webform_entity_reference_item', $service_definition);
    }

  }

}
