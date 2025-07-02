<?php

declare(strict_types=1);

namespace Drupal\google_tag;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider for google_tag.
 */
final class GoogleTagServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // We cannot use the module handler as the container is not yet compiled.
    // @see \Drupal\Core\DrupalKernel::compileContainer()
    $modules = $container->getParameter('container.modules');

    $subscribers_by_dependency = [
      'csp' => 'google_tag.csp_subscriber',
      'commerce_cart' => 'google_tag.commerce_cart_subscriber',
      'commerce_order' => 'google_tag.commerce_order_subscriber',
      'commerce_product' => 'google_tag.commerce_product_subscriber',
      'commerce_wishlist' => 'google_tag.commerce_wishlist_subscriber',
      'search_api' => 'google_tag.search_api_subscriber',
    ];
    foreach ($subscribers_by_dependency as $dependency => $service) {
      if (!isset($modules[$dependency])) {
        $container->removeDefinition($service);
      }
    }
  }

}
