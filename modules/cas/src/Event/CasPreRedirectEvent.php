<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasRedirectData;
use Drupal\cas\CasServerConfig;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreRedirectEvent.
 *
 * CAS dispatches this event just before a user is redirected to the CAS server
 * for authentication.
 *
 * Subscribers of this event can:
 *  - Add query string parameters to the CAS server URL. This is useful if your
 *    CAS server requires extra data to be sent during authentication.
 *  - Add query string parameters to the "service URL" (the URL users are
 *    returned to after authenticating with the CAS server). This is useful if
 *    you want to pass data back to your Drupal site after the authentication
 *    process is completed.
 *  - Prevent the authentication redirect entirely. This is only applicable if
 *    the user was being redirected for a Forced Login or Gateway Login.
 *    Users that visit /caslogin (or /cas) will always be redirected to the CAS
 *    server no matter what.
 *  - Indicate if the redirect to the CAS server is a cacheable redirect and if
 *    so, add cache tags and cache contexts to the redirect response.
 */
class CasPreRedirectEvent extends Event {

  /**
   * Data used to decide on final redirection.
   *
   * @var \Drupal\cas\CasRedirectData
   */
  protected $casRedirectData;

  /**
   * Data object representing the CAS server configuration to redirect to.
   *
   * @var \Drupal\cas\CasServerConfig
   */
  protected $casServerConfig;

  /**
   * CasPreRedirectEvent constructor.
   *
   * @param \Drupal\cas\CasRedirectData $cas_redirect_data
   *   The redirect data object.
   * @param \Drupal\cas\CasServerConfig $cas_server_config
   *   The CAS server config data object.
   */
  public function __construct(CasRedirectData $cas_redirect_data, CasServerConfig $cas_server_config) {
    $this->casRedirectData = $cas_redirect_data;
    $this->casServerConfig = $cas_server_config;
  }

  /**
   * Getter for $casRedirectData.
   *
   * @return \Drupal\cas\CasRedirectData
   *   The redirect data object.
   */
  public function getCasRedirectData() {
    return $this->casRedirectData;
  }

  /**
   * Get the CAS server config for the server to redirect to.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The config, which can be modified.
   */
  public function getCasServerConfig() {
    return $this->casServerConfig;
  }

}
