<?php

namespace Drupal\masquerade\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\masquerade\Masquerade;
use Drupal\user\EventSubscriber\UserRequestSubscriber;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Decorates service user_last_access_subscriber to prevent user's data changes.
 *
 * @internal
 *   Implementation could change later.
 */
class MasqueradeUserRequestSubscriber extends UserRequestSubscriber {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * {@inheritdoc}
   */
  public function onKernelTerminate(TerminateEvent $event) {
    $force = (bool) $this->configFactory
      ->get('masquerade.settings')
      ->get('update_user_last_access');
    if (!$this->masquerade->isMasquerading() || $force) {
      parent::onKernelTerminate($event);
    }
  }

  /**
   * Initialises masquerade required services.
   *
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function setMasquerade(Masquerade $masquerade, ConfigFactoryInterface $configFactory) {
    $this->masquerade = $masquerade;
    $this->configFactory = $configFactory;
  }

}
