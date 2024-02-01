<?php

namespace Drupal\az_cas\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a AzCasRouteSubscriber.
 */
class AzCasRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('az_cas.settings')->get('disable_password_recovery_link')) {
      $collection->get('user.pass')->setRequirement('_access', 'FALSE');
      $collection->get('user.pass.http')->setRequirement('_access', 'FALSE');
    }
  }

}
