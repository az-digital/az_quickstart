<?php

namespace Drupal\az_core\Http;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Utils;

/**
 * Helper class to construct a HTTP client with a self-identified user agent.
 */
class AZClientFactory extends ClientFactory {

  /**
   * {@inheritdoc}
   */
  public function fromOptions(array $config = []) {
    // Get the base url of the site.
    // Cannot use the request object here, there may be no request.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    $url = $url->toString();

    $default_config = [
      // Security consideration: we must not use the certificate authority
      // file shipped with Guzzle because it can easily get outdated if a
      // certificate authority is hacked. Instead, we rely on the certificate
      // authority file provided by the operating system which is more likely
      // going to be updated in a timely fashion. This overrides the default
      // path to the pem file bundled with Guzzle.
      'verify' => TRUE,
      'timeout' => 30,
      'headers' => [
        // Use the base url of the site in the normal user agent string.
        // @see \Drupal\Core\Http\ClientFactory
        'User-Agent' => 'Drupal/' . \Drupal::VERSION . ' (+' . $url . ') ' . Utils::defaultUserAgent(),
      ],
      'handler' => $this->stack,
      // Security consideration: prevent Guzzle from using environment variables
      // to configure the outbound proxy.
      'proxy' => [
        'http' => NULL,
        'https' => NULL,
        'no' => [],
      ],
    ];

    $config = NestedArray::mergeDeep($default_config, Settings::get('http_client_config', []), $config);

    return new Client($config);
  }

}
