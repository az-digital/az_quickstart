<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the CAS forced login controller.
 */
abstract class CasBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cas'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tell mink not to automatically follow redirects.
   */
  protected function disableRedirects() {
    $this->getSession()->getDriver()->getClient()->followRedirects(FALSE);
  }

  /**
   * Tell mink to follow redirects.
   */
  protected function enabledRedirects() {
    $this->getSession()->getDriver()->getClient()->followRedirects(TRUE);
  }

  /**
   * Helper function for constructing an expected service URL.
   *
   * Any parameters passed into the optional array will be appended to the
   * service URL.
   *
   * @param array $service_url_params
   *   Parameters to include on the service url.
   *
   * @return string
   *   URL in string format.
   */
  protected function buildServiceUrlWithParams(array $service_url_params = []) {
    $service_url = $this->baseUrl . '/casservice';
    if (!empty($service_url_params)) {
      $encoded_params = UrlHelper::buildQuery($service_url_params);
      $service_url .= '?' . $encoded_params;
    }
    return $service_url;
  }

}
