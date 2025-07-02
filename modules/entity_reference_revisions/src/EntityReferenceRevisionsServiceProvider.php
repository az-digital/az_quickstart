<?php

namespace Drupal\entity_reference_revisions;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\entity_reference_revisions\Normalizer\EntityReferenceRevisionItemNormalizer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Entity Reference Revisions.
 */
class EntityReferenceRevisionsServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['hal'])) {
      // Hal module is enabled, add our new normalizer for entity reference
      // revision items. For Drupal 10, the normalizer has been moved to the
      // hal contrib module.
      if (version_compare(\Drupal::VERSION, '10', '<')) {
        $service_definition = new Definition(EntityReferenceRevisionItemNormalizer::class, array(
          new Reference('hal.link_manager'),
          new Reference('serializer.entity_resolver'),
        ));
        // The priority must be higher than that of
        // serializer.normalizer.entity_reference_item.hal in
        // hal.services.yml.
        $service_definition->setPublic(TRUE);
        $service_definition->addTag('normalizer', array('priority' => 20));
        $container->setDefinition('serializer.normalizer.entity_reference_revision_item', $service_definition);
      }
    }
  }

}
