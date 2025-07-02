<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Psr\Http\Message\ResponseInterface;

/**
 * Defines an interface for data fetchers.
 *
 * @see \Drupal\migrate_plus\Annotation\DataFetcher
 * @see \Drupal\migrate_plus\DataFetchPluginBase
 * @see \Drupal\migrate_plus\DataFetcherPluginManager
 * @see plugin_api
 */
interface DataFetcherPluginInterface {

  /**
   * Set the client headers.
   *
   * @param array $headers
   *   An array of the headers to set on the HTTP request.
   */
  public function setRequestHeaders(array $headers): void;

  /**
   * Get the currently set request headers.
   */
  public function getRequestHeaders(): array;

  /**
   * Return content.
   *
   * @param string $url
   *   URL to retrieve from.
   *
   * @return string
   *   Content at the given url.
   */
  public function getResponseContent(string $url): string;

  /**
   * Return Http Response object for a given url.
   *
   * @param string $url
   *   URL to retrieve from.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response message.
   */
  public function getResponse(string $url): ResponseInterface;

  /**
   * Collect next urls from the metadata of a paged response.
   *
   * Examples of this include HTTP headers and file naming conventions.
   *
   * @param string $url
   *   URL of the resource to check for pager metadata.
   *
   * @return array
   *   Array of URIs.
   */
  public function getNextUrls(string $url): array;

}
