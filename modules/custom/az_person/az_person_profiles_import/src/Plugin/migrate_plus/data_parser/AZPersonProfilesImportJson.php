<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for migration from Profiles Integration.
 *
 * @DataParser(
 *   id = "az_person_profiles_import_json",
 *   title = @Translation("Profiles Integration JSON")
 * )
 */
class AZPersonProfilesImportJson extends Json {

  /**
   * {@inheritdoc}
   */
  protected function getSourceData(string $url, string|int $item_selector = ''): array {
    $source_data = parent::getSourceData($url);

    // API returned nothing for this netid.
    if (empty($source_data)) {
      return [];
    }

    // Profiles returns a single item, not an array as the Json parser expects.
    return [$source_data];
  }

}
