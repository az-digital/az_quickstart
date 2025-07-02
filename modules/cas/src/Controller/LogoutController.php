<?php

namespace Drupal\cas\Controller;

use Drupal\cas\CasRedirectResponse;
use Drupal\cas\CasServerConfig;
use Drupal\cas\Service\CasHelper;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class LogoutController.
 *
 * Logs a user out of Drupal then logs them out of the CAS server.
 */
class LogoutController implements ContainerInjectionInterface {

  /**
   * The cas helper used to get settings from.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * The request stack to get the request object from.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores the URL generator.
   *
   * @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CasHelper to get the logout Url from.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The current request stack, to provide context.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\Routing\Generator\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(CasHelper $cas_helper, RequestStack $request_stack, ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator) {
    $this->casHelper = $cas_helper;
    $this->requestStack = $request_stack;
    $this->settings = $config_factory->get('cas.settings');
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.helper'), $container->get('request_stack'), $container->get('config.factory'), $container->get('url_generator'));
  }

  /**
   * Logs a user out of Drupal, then redirects them to the CAS server logout.
   */
  public function logout() {
    // First log the user out of Drupal.
    user_logout();

    // Redirect the user to the CAS server for logging out there as well.
    $cas_server_logout_url = $this->getServerLogoutUrl($this->requestStack->getCurrentRequest());
    $this->casHelper->log(
      LogLevel::DEBUG,
      "Drupal session terminated; redirecting to CAS server logout at %url",
      ['%url' => $cas_server_logout_url]
    );
    return new CasRedirectResponse($cas_server_logout_url, 302);
  }

  /**
   * Return the logout URL for the CAS server.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request, to provide base url context.
   *
   * @return string
   *   The fully constructed server logout URL.
   */
  public function getServerLogoutUrl(Request $request) {
    // @todo Allow cas server config to be altered.
    $casServerConfig = CasServerConfig::createFromModuleConfig($this->settings);
    $base_url = $casServerConfig->getServerBaseUrl() . 'logout';

    // CAS servers can redirect a user to some other URL after they end
    // the user session. Check if we're configured to send along this
    // destination parameter and pass it along if so.
    if (!empty($this->settings->get('logout.logout_destination'))) {
      $destination = $this->settings->get('logout.logout_destination');
      if ($destination == '<front>') {
        // If we have '<front>', resolve the path.
        $return_url = $this->urlGenerator->generate($destination, [], UrlGeneratorInterface::ABSOLUTE_URL);
      }
      elseif (UrlHelper::isExternal($destination)) {
        // If we have an absolute URL, use that.
        $return_url = $destination;
      }
      else {
        // This is a regular Drupal path.
        $return_url = $request->getSchemeAndHttpHost() . '/' . ltrim($destination, '/');
      }

      // CAS 2.0 uses 'url' param, while newer versions use 'service'.
      if ($casServerConfig->getProtocolVerison() == '2.0') {
        $params['url'] = $return_url;
      }
      else {
        $params['service'] = $return_url;
      }

      return $base_url . '?' . UrlHelper::buildQuery($params);
    }
    else {
      return $base_url;
    }
  }

}
