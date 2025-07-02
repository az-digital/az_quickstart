<?php

namespace Drupal\cas\Service;

use Drupal\cas\CasRedirectData;
use Drupal\cas\CasRedirectResponse;
use Drupal\cas\CasServerConfig;
use Drupal\cas\Event\CasPreRedirectEvent;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Helper class that builds the redirect response.
 */
class CasRedirector {

  /**
   * The CasHelper.
   *
   * @var CasHelper
   */
  protected $casHelper;

  /**
   * The EventDispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Stores URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores CAS settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * CasRedirector constructor.
   *
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CasHelper service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The EventDispatcher service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(CasHelper $cas_helper, EventDispatcherInterface $event_dispatcher, UrlGeneratorInterface $url_generator, ConfigFactoryInterface $config_factory) {
    $this->casHelper = $cas_helper;
    $this->eventDispatcher = $event_dispatcher;
    $this->urlGenerator = $url_generator;
    $this->settings = $config_factory->get('cas.settings');
  }

  /**
   * Determine login URL response.
   *
   * @param \Drupal\cas\CasRedirectData $data
   *   Data used to generate redirector.
   * @param bool $force
   *   True implies that you always want to generate a redirector as occurs with
   *   the ForceRedirectController. False implies redirector is controlled by
   *   the allow_redirect property in the CasRedirectData object.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Drupal\cas\CasRedirectResponse|null
   *   The RedirectResponse or NULL if a redirect shouldn't be done.
   */
  public function buildRedirectResponse(CasRedirectData $data, $force = FALSE) {
    $response = NULL;

    $casServerConfig = CasServerConfig::createFromModuleConfig($this->settings);

    // Dispatch an event that allows modules to alter or prevent the redirect,
    // or to change the CAS server that we're redirected to.
    $pre_redirect_event = new CasPreRedirectEvent($data, $casServerConfig);
    $this->eventDispatcher->dispatch($pre_redirect_event, CasHelper::EVENT_PRE_REDIRECT);

    // Build the service URL, which is where the CAS server will send users
    // back to after authenticating them. We always send users back to our main
    // service controller, but there can be additional query params to attach
    // to that request as well.
    $service_parameters = $data->getAllServiceParameters();
    $parameters = $data->getAllParameters();
    $parameters['service'] = $this->urlGenerator->generate('cas.service', $service_parameters, UrlGeneratorInterface::ABSOLUTE_URL);

    $login_url = $casServerConfig->getServerBaseUrl() . 'login?' . UrlHelper::buildQuery($parameters);

    // Get the redirection response.
    if ($force || $data->willRedirect()) {
      // $force implies we are on the /cas url or equivalent, so we
      // always want to redirect and data is always cacheable.
      if (!$force && !$data->getIsCacheable()) {
        return new CasRedirectResponse($login_url);
      }
      else {
        $cacheable_metadata = new CacheableMetadata();
        // Add caching metadata from CasRedirectData.
        if (!empty($data->getCacheTags())) {
          $cacheable_metadata->addCacheTags($data->getCacheTags());
        }
        if (!empty($data->getCacheContexts())) {
          $cacheable_metadata->addCacheContexts($data->getCacheContexts());
        }
        $response = new TrustedRedirectResponse($login_url);
        $response->addCacheableDependency($cacheable_metadata);
      }
      $this->casHelper->log(LogLevel::DEBUG, "Built CAS redirect URL to %url", ['%url' => $login_url]);
    }
    return $response;
  }

}
