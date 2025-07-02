<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasServerConfig;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreValidateServerConfigEvent.
 *
 * Subscribers of this event can modify the configuration of the CAS server
 * before it's used to make the validation request. A module could do this to
 * validate the ticket on some other server rather than the one defined in
 * the default configuration.
 */
class CasPreValidateServerConfigEvent extends Event {

  /**
   * The CAS server config value object.
   *
   * @var \Drupal\cas\CasServerConfig
   */
  protected $casServerConfig;

  /**
   * CasPreValidateServerConfigEvent constructor.
   *
   * @param \Drupal\cas\CasServerConfig $casServerConfig
   *   The CAS server config value object.
   */
  public function __construct(CasServerConfig $casServerConfig) {
    $this->casServerConfig = $casServerConfig;
  }

  /**
   * Returns the CAS server config.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The CAS server config.
   */
  public function getCasServerConfig(): CasServerConfig {
    return $this->casServerConfig;
  }

}
