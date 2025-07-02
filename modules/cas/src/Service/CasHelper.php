<?php

namespace Drupal\cas\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Utility\Token;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Utility and helper methods.
 */
class CasHelper {

  /**
   * SSL configuration to use the system's CA bundle to verify CAS server.
   *
   * @var int
   */
  const CA_DEFAULT = 0;

  /**
   * SSL configuration to use provided file to verify CAS server.
   *
   * @var int
   */
  const CA_CUSTOM = 1;

  /**
   * SSL Configuration to not verify CAS server.
   *
   * @var int
   */
  const CA_NONE = 2;

  /**
   * Event type identifier for the CasPreUserLoadEvent.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD = 'cas.pre_user_load';

  /**
   * Event type identifier for the CasPreUserLoadRedirectEvent event.
   *
   * @var string
   */
  const EVENT_PRE_USER_LOAD_REDIRECT = 'cas.pre_user_load.redirect';

  /**
   * Event type identifier for the CasPreRegisterEvent.
   *
   * @var string
   */
  const EVENT_PRE_REGISTER = 'cas.pre_register';

  /**
   * Event type identifier for the CasPreLoginEvent.
   *
   * @var string
   */
  const EVENT_PRE_LOGIN = 'cas.pre_login';

  /**
   * Event type identifier for pre auth events.
   *
   * @var string
   */
  const EVENT_PRE_REDIRECT = 'cas.pre_redirect';

  /**
   * Event to modify CAS server config before it's used to validate a ticket.
   */
  const EVENT_PRE_VALIDATE_SERVER_CONFIG = 'cas.pre_validate_server_config';

  /**
   * Event type identifier for pre validation events.
   *
   * @var string
   */
  const EVENT_PRE_VALIDATE = 'cas.pre_validate';

  /**
   * Event type identifier for events fired after service validation.
   *
   * @var string
   */
  const EVENT_POST_VALIDATE = 'cas.post_validate';

  /**
   * Event type identifier for events fired after login has completed.
   */
  const EVENT_POST_LOGIN = 'cas.post_login';

  /**
   * Indicates gateway redirect performed server-side.
   */
  const GATEWAY_SERVER_SIDE = 'server_side';

  /**
   * Indicates gateway redirect performed client-side.
   */
  const GATEWAY_CLIENT_SIDE = 'client_side';

  /**
   * A list of routes we should never trigger CAS login redirect on.
   */
  const IGNOREABLE_AUTO_LOGIN_ROUTES = [
    'cas.service',
    'cas.proxyCallback',
    'cas.login',
    'cas.legacy_login',
    'cas.logout',
    'image.style_private',
    'image.style_public',
    'system.cron',
    'system.css_asset',
    'system.js_asset',
    'user.logout',
    'user.logout.http',
  ];

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, Token $token) {
    $this->settings = $config_factory->get('cas.settings');
    $this->loggerChannel = $logger_factory->get('cas');
    $this->token = $token;
  }

  /**
   * Wrap Drupal's normal logger.
   *
   * This allows us to only log debug messages if configured to do so.
   *
   * @param mixed $level
   *   The message to log.
   * @param string $message
   *   The error message.
   * @param array $context
   *   The context.
   */
  public function log($level, $message, array $context = []) {
    // Back out of logging if it's a debug message and we're not configured
    // to log those types of messages. This helps keep the drupal log clean
    // on busy sites.
    if ($level == LogLevel::DEBUG && !$this->settings->get('advanced.debug_log')) {
      return;
    }
    $this->loggerChannel->log($level, $message, $context);
  }

  /**
   * Converts a "returnto" query param to a "destination" query param.
   *
   * This method is used in support of the deprecated method for creating CAS
   * login links that return users to a specific page after login,
   * e.g. /cas?returnto=/some/page.
   *
   * Since version 2.0.0, using the "returnto" query param for this purpose
   * is deprecated and will be removed in version 3.0.0.
   *
   * It has since been replaced with Drupal's standard method of redirecting
   * users to some page after an action via the "destination" query param,
   * e.g. /cas?destination=/some/page.
   *
   * Note that, even deprecated, "returnto" takes precedence over "destination",
   * if both are passed as query parameters, in order to ensure backwards
   * compatibility.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Symfony request object.
   *
   * @deprecated in cas:2.0.0 and is removed from cas:3.0.0. No replacement is
   *   provided.
   *
   * @see https://www.drupal.org/node/3231208
   */
  public function handleReturnToParameter(Request $request) {
    // Convert the "returnto" parameter to "destination" so that core's
    // RedirectResponseSubscriber can take over and actually redirect the user
    // to that location if set.
    if ($request->query->has('returnto')) {
      @trigger_error("Using the 'returnto' query parameter in order to redirect to a destination after login is deprecated in cas:2.0.0 and removed from cas:3.0.0. Use 'destination' query parameter instead. See https://www.drupal.org/node/3231208", E_USER_DEPRECATED);
      $this->log(LogLevel::DEBUG, "Converting query parameter 'returnto' to 'destination'.");
      $request->query->set('destination', $request->query->get('returnto'));
    }
  }

  /**
   * Returns a translated configurable message given the message config key.
   *
   * @param string $key
   *   The message config key.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The customized message or an empty string.
   *
   * @throws \InvalidArgumentException
   *   If the passed key don't match a config entry.
   */
  public function getMessage($key) {
    assert($key && is_string($key));
    $message = $this->settings->get($key);
    if ($message === NULL || !is_string($message)) {
      throw new \InvalidArgumentException("Invalid key '$key'");
    }

    // Empty string.
    if (!$message) {
      return '';
    }

    return new FormattableMarkup(Xss::filter($this->token->replace($message)), []);
  }

}
