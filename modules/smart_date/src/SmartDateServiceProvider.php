<?php

namespace Drupal\smart_date;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\smart_date\Normalizer\SmartDateItemNormalizer;
use Drupal\smart_date\Normalizer\SmartDateNormalizer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Smart Date.
 */
class SmartDateServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['serialization'])) {
      // Serialization module is enabled, add our Smart Date normalizer.
      // Priority of the normalizer must be higher than other
      // general-purpose typed data and field item normalizers.
      $smart_date = new Definition(SmartDateNormalizer::class);
      $smart_date->addTag('normalizer', ['priority' => 30]);
      $smart_date->addArgument(new Reference('config.factory'));
      $container->setDefinition('smart_date.normalizer.smartdate', $smart_date);

      $smart_date = new Definition(SmartDateItemNormalizer::class);
      $smart_date->addTag('normalizer', ['priority' => 9]);
      $container->setDefinition('smart_date.normalizer.smartdate_item', $smart_date);

    }
    if (isset($modules['hal'])) {
      // HAL module is enabled, add our Smart Date normalizer.
      // Priority of the normalizer must be higher than other
      // general-purpose typed data and field item normalizers.
      $smart_date = new Definition(SmartDateItemNormalizer::class);
      $smart_date->addTag('normalizer', ['priority' => 30]);
      $container->setDefinition('smart_date.normalizer.smartdate_item.hal', $smart_date);
    }
  }

}
