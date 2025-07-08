<?php

namespace Drupal\az_course;

use Drupal\Core\Url;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Constructs URLs for Courses API and performs subject-wide queries.
 */
class CourseSearch {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Logger\LoggerChannelInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The source URLs to retrieve.
   *
   * @var string
   */
  protected $courseDetailsUrl = "https://uacourses-api.uaccess.arizona.edu/crsdetail";

  /**
   * Constructs a CourseSearch service.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A guzzle client for making http requests.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelInterface $logger) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Generate a URL for a known subject code and catalog number.
   *
   * @param string $subject
   *   A subject code, e.g. MATH.
   * @param string $catalog
   *   A catalog number, e.g. 101.
   *
   * @return array
   *   An array of urls to migrate.
   */
  public function fetchUrls($subject, $catalog) {

    $urls = [];
    $queryUrl = Url::fromUri($this->courseDetailsUrl, [
      'query' => [
        'subject_code' => strtoupper($subject),
        'catalog_nbr' => strtoupper($catalog),
      ],
    ]);
    $urls[] = $queryUrl->toString();

    return $urls;
  }

  /**
   * Find service URL options for a search string, typically a subject code.
   *
   * @param string $search
   *   A search string, typically a subject code, e.g. MATH.
   *
   * @return array
   *   An array of urls to migrate.
   */
  public function fetchOptions($search) {
    $options = [];
    $items = [];
    $itemSelector = 'ns1:Course';
    $subjectSelector = 'ns1:subject_code';
    $catalogSelector = 'ns1:catalog_nbr';
    $descriptionSelector = 'ns1:descr';

    $search = strtoupper($search);
    $queryUrl = Url::fromUri('https://uacourses-api.uaccess.arizona.edu/search', ['query' => ['search_text' => $search]]);
    $queryUrl = $queryUrl->toString();
    try {
      $response = $this->httpClient->request('GET', $queryUrl);
      $body = $response->getBody();
      $xml = new \XMLReader();
      if ($xml->xml($body)) {
        $item = [];
        while ($xml->read()) {
          if ($xml->nodeType === \XMLReader::ELEMENT) {
            if ($xml->name === $itemSelector) {
              $item = [];
            }
            if ($xml->name === $subjectSelector) {
              $item['subject'] = $xml->readInnerXML();
            }
            if ($xml->name === $catalogSelector) {
              $item['catalog'] = $xml->readInnerXML();
            }
            if ($xml->name === $descriptionSelector) {
              $item['description'] = $xml->readInnerXML();
            }
          }
          if ($xml->nodeType === \XMLReader::END_ELEMENT) {
            if ($xml->name === $itemSelector) {
              $items[] = $item;
            }
          }
        }
        $xml->close();
      }
    }
    catch (RequestException $e) {
      $this->logger->error("Request exception.");
    }

    foreach ($items as $item) {
      if (!empty($item['subject']) && !empty($item['catalog']) && !empty($item['description'])) {
        $queryUrl = Url::fromUri($this->courseDetailsUrl, [
          'query' => [
            'subject_code' => strtoupper($item['subject']),
            'catalog_nbr' => strtoupper($item['catalog']),
          ],
        ]
        );
        $options[] = $queryUrl->toString();
      }
    }

    return $options;
  }

}
