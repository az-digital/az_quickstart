<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataFetcherPluginBase;

/**
 * Retrieve data from a local path or general URL for migration.
 *
 * @DataFetcher(
 *   id = "file",
 *   title = @Translation("File")
 * )
 */
class File extends DataFetcherPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setRequestHeaders(array $headers): void {
    // Does nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestHeaders(): array {
    // Does nothing.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url): ResponseInterface {
    $response = FALSE;
    if (!empty($url)) {
      $response = @file_get_contents($url);
    }
    if ($response === FALSE) {
      throw new MigrateException('file parser plugin: could not retrieve data from ' . $url);
    }
    return new Response(200, [], $response);
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent(string $url): string {
    return (string) $this->getResponse($url)->getBody();
  }

}
