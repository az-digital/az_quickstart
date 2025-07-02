<?php

declare(strict_types=1);

namespace Drupal\entity_embed_test\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\entity_embed_test\Controller\TestEntityEmbedController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('embed.preview')) {
      $route->setDefault('_controller', TestEntityEmbedController::class . '::preview');
    }
  }

}
