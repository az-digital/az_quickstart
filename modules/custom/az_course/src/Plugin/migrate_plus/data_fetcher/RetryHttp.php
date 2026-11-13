<?php

namespace Drupal\az_course\Plugin\migrate_plus\data_fetcher;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

/**
 * Retrieve data over an HTTP connection for migration. Retry if necessary.
 *
 * @deprecated in az_quickstart:3.6.0 and is removed from az_quickstart:4.0.0.
 * Use http or az_http instead. az_http incorporates the retry behavior.
 *
 * phpcs:ignore Drupal.Commenting.Deprecated.DeprecatedWrongSeeUrlFormat
 * @see https://github.com/az-digital/az_quickstart/issues/6117
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_retry_http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "retry_http",
 *   title = @Translation("AZ Retry HTTP")
 * )
 */
class RetryHttp extends Http {

  const MAX_REQUESTS = 5;

  /**
   * {@inheritdoc}
   */
  public function getResponse($url): ResponseInterface {
    // Schedule sometimes returns 500 during outages.
    for ($i = 0; $i < self::MAX_REQUESTS; $i++) {
      try {
        $options = ['headers' => $this->getRequestHeaders()];
        if (!empty($this->configuration['authentication'])) {
          $options = array_merge($options, $this->getAuthenticationPlugin()->getAuthenticationOptions($url));
        }
        if (!empty($this->configuration['request_options'])) {
          $options = array_merge($options, $this->configuration['request_options']);
        }
        $response = $this->httpClient->get($url, $options);
        return $response;
      }
      catch (RequestException $e) {
      }
      catch (ClientException $e) {
      }
      catch (ServerException $e) {
      }

      sleep(1);
    }
    throw new MigrateException('No response at ' . $url . '.');
  }

}
