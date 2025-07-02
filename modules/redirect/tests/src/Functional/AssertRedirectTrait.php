<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\Core\Url;
use GuzzleHttp\Exception\ClientException;

/**
 * Asserts the redirect from a given path to the expected destination path.
 */
trait AssertRedirectTrait {

  /**
   * Asserts the redirect from $path to the $expected_ending_url.
   *
   * @param string $path
   *   The request path.
   * @param $expected_ending_url
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param int $expected_ending_status
   *   The status we expect to get with the first request.
   * @param string $method
   *   The HTTP METHOD to use.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function assertRedirect($path, $expected_ending_url, $expected_ending_status = 301, $method = 'GET') {
    $client = $this->getHttpClient();
    /** @var \Psr\Http\Message\ResponseInterface $response */
    $url = $this->getAbsoluteUrl($path);
    try {
      $response = $client->request($method, $url, ['allow_redirects' => FALSE]);
    }
    catch (ClientException $e) {
      $this->assertEquals($expected_ending_status, $e->getResponse()->getStatusCode());
      return $e->getResponse();
    }

    $this->assertEquals($expected_ending_status, $response->getStatusCode());

    $ending_url = $response->getHeader('location');
    $ending_url = $ending_url ? $ending_url[0] : NULL;
    $message = "Testing redirect from $path to $expected_ending_url. Ending url: $ending_url";

    if ($expected_ending_url == '<front>') {
      $expected_ending_url = Url::fromUri('base:')->setAbsolute()->toString();
    }
    elseif (!empty($expected_ending_url)) {
      // Check for absolute/external urls.
      if (!parse_url($expected_ending_url, PHP_URL_SCHEME)) {
        $expected_ending_url = Url::fromUri('base:' . $expected_ending_url)->setAbsolute()->toString();
      }
    }
    else {
      $expected_ending_url = NULL;
    }

    $this->assertEquals($ending_url, $expected_ending_url, $message);
    return $response;
  }

}
