<?php

namespace Drupal\az_enterprise_attributes_import\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain Normalized JSON data for migration.
 *
 * @DataParser(
 *   id = "az_enterprise_attributes_import_json",
 *   title = @Translation("Enterprise Attributes JSON")
 * )
 */
class AZEnterpriseAttributesJson extends Json {

  /**
   * {@inheritdoc}
   */
  protected function getSourceData($url): array {
    $source_data = parent::getSourceData($url);
    // Create nested children.
    foreach ($source_data as $index => $item) {
      // Add child terms to data.
      if (!empty($item['values'])) {
        foreach ($item['values'] as $child) {
          if (!empty($item['name'])) {
            $child['parent'] = $item['name'];
          }
          $source_data[] = $child;
        }
      }
    }
    // Preprocessing before field selection to normalize.
    foreach ($source_data as $index => $item) {
      // Add an empty parent if there isn't one.
      $source_data[$index]['parent'] = $item['parent'] ?? '';
      // If there is no name, the value is the name.
      if (empty($item['name']) && !empty($item['value'])) {
        $source_data[$index]['name'] = $item['value'];
      }
      // If there is no key, the name is the key.
      if (empty($item['key']) && !empty($item['name'])) {
        $source_data[$index]['key'] = $item['name'];
      }
    }
    return $source_data;
  }

}
