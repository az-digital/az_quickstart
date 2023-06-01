<?php

namespace Drupal\az_core\Plugin\LinkExtractor;

use Drupal\linkchecker\Plugin\LinkExtractor\HtmlLinkExtractor;

/**
 * Extracts link from field.
 *
 * @LinkExtractor(
 *   id = "az_link_extractor",
 *   label = @Translation("Quickstart field link extractor"),
 *   field_types = {
 *     "az_card",
 *     "az_accordion",
 *   }
 * )
 */
class AzLinkExtractor extends HtmlLinkExtractor {

  /**
   * Extracts a URLs from field.
   *
   * @param array $value
   *   The field value.
   *
   * @return array
   *   Array of URLs.
   */
  protected function extractUrlFromLinkUriField(array $value) {
    // Return the uri index from the $value array.
    return empty($value['link_uri']) ? [] : [$value['link_uri']];
  }

  /**
   * {@inheritdoc}
   */
  protected function extractUrlFromField(array $value) {
    $link_uri = $this->extractUrlFromLinkUriField($value);
    $value['value'] = $value['body'];
    $body = parent::extractUrlFromField($value);

    return array_merge($link_uri, $body);
  }

}
