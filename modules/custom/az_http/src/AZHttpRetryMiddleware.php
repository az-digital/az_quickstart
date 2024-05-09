<?php

namespace Drupal\az_http;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides a Middleware that conducts retries for failed requests.
 */
class AZHttpRetryMiddleware {

  /**
   * Maximum number of requests to perform.
   *
   * @var int
   */
  protected $requests;

  /**
   * Constructs a new AZHttpRetryMiddleware.
   *
   * @param int $max_requests
   *   Maximum number of requests to perform.
   */
  public function __construct($max_requests) {
    $this->requests = $max_requests;
  }

  /**
   * Invokes to a callable that retries failed requests in a handler stack.
   *
   * @return callable
   *   A callable that provides a middleware with a retry decision function.
   */
  public function __invoke(): callable {
    return Middleware::retry([$this, 'decideRetry']);
  }

  /**
   * Decide whether to retry a request.
   *
   * @param int $retries
   *   The number of retries that have taken place.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request that was last made.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response, if one occurred.
   * @param \GuzzleHttp\Exception\RequestException $exception
   *   A potential exception that occurred during the request.
   *
   * @return bool
   *   Whether to retry the request or not.
   */
  public function decideRetry($retries, RequestInterface $request, ResponseInterface $response = NULL, RequestException $exception = NULL) {
    // Abort if we are beyond our limit.
    if ($retries >= $this->requests) {
      return FALSE;
    }

    // Retry if an exception occurred.
    if (!empty($exception)) {
      return TRUE;
    }

    // Retry if we received a response that indicated unavailability.
    if (!empty($response) && $response->getStatusCode() >= 500) {
      return TRUE;
    }

    return FALSE;
  }

}
