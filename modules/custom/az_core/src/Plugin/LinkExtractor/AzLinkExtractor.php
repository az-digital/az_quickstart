<?php

namespace Drupal\az_core\Plugin\LinkExtractor;

use Drupal\linkchecker\Plugin\LinkExtractorBase;

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
class AzLinkExtractor extends LinkExtractorBase {

  /**
   * {@inheritdoc}
   */
  protected function extractUrlFromField(array $value) {
    // Return the uri index from the $value array.
    return empty($value['link_uri']) ? [] : [$value['link_uri']];
  }

}
