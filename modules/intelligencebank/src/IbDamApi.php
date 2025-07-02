<?php

namespace Drupal\ib_dam;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadRequest;
use GuzzleHttp\Exception\RequestException;

/**
 * IbDamApi service to fetch remote files.
 *
 * @package Drupal\ib_dam
 */
final class IbDamApi {

  private $sessionId;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger channel for IntelligenceBank DAM.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client factory object.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger chanel for IntelligenceBank DAM.
   */
  public function __construct(ClientFactory $http_client_factory, LoggerChannelInterface $loggerChannel) {
    $this->httpClient = $http_client_factory->fromOptions([
      'timeout' => Settings::get('intelligencebank_api_timeout', 120)
    ]);
    $this->logger = $loggerChannel;
  }

  /**
   * Set Session ID property to use in any request to the remote IB api.
   */
  public function setSessionId(string $sid) {
    $this->sessionId = $sid;
    return $this;
  }

  /**
   * Creates request and run it.
   *
   * @param string $url
   *   The url of the resource.
   *
   * @return null|\Psr\Http\Message\ResponseInterface
   *   Response object.
   */
  public function fetchResource($url, bool $useHeaders = TRUE) {
    if (!$this->isValidRequestParams($url)) {
      return NULL;
    }

    try {
      // @todo: try to retry a couple of times, see https://goo.gl/qcz3DG.
      $response = $this->httpClient->get($url, $useHeaders
        ? $this->getHeaders()
        : []
      );
    }
    catch (RequestException $e) {
      $params  = $this->getLogParams($url);
      (new AssetDownloaderBadRequest($e->getMessage(), $params))
        ->logException();
      return NULL;
    }

    return $response;
  }

  /**
   * Set default header for request validation on remote side.
   */
  private function getHeaders():array {
    return [
      'headers' => [
        'sid'    => $this->sessionId,
      ],
    ];
  }

  /**
   * Check if request url is valid and auth key is ready to use.
   */
  private function isValidRequestParams($url) {
    if (!$url) {
      $params  = $this->getLogParams($url);
      $message = 'Missing required params.';

      (new AssetDownloaderBadRequest($message, $params))
        ->logException();
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper function to log error messages.
   */
  private function getLogParams($url = NULL) {
    return [
      'url' => $url ?: 'No url',
    ];
  }

}
