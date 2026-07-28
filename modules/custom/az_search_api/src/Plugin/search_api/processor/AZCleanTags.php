<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Transforms base_url into a value matching xmlsitemap.
 */
#[SearchApiProcessor(
  id: 'az_clean_tags',
  label: new TranslatableMarkup('Clean Tags (Quickstart)'),
  description: new TranslatableMarkup('Forces lowercase and changes whitespace to hyphens.'),
  stages: [
    'pre_index_save' => 0,
    'preprocess_index' => 15,
    'preprocess_query' => 15,
  ],
)]
class AZCleanTags extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // Create a predictable slug value, lowercase with hypthenated words.
    $value = preg_replace('/\s+/', '-', strtolower($value));
  }

}
