<?php

namespace Drupal\schema_metatag_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the schema_metatag.schema_metatag_client service for tests.
 */
class SchemaMetatagTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('schema_metatag.schema_metatag_client');
    $definition->setClass(SchemaMetatagClient::class);
  }

}
