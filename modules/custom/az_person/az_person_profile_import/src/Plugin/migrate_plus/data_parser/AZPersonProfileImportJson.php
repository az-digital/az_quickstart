<?php

declare(strict_types=1);

namespace Drupal\az_person_profile_import\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;

/**
 * Obtain JSON data for migration from Profiles Integration.
 *
 * @DataParser(
 *   id = "az_person_profile_import_json",
 *   title = @Translation("Profiles Integration JSON")
 * )
 */
class AZPersonProfileImportJson extends Json {

  /**
   * {@inheritdoc}
   */
  protected function getSourceData(string $url, string|int $item_selector = ''): array {
    $source_data = parent::getSourceData($url);

    // API returned nothing for this netid.
    if (empty($source_data)) {
      return [];
    }

    // We'll attempt to do some formatting on the phone number.
    if (!empty($source_data['Person']['phone'])) {
      $phones = $source_data['Person']['phone'];
      if (is_array($phones)) {
        foreach ($phones as &$phone) {
          if (empty($phone['number'])) {
            continue;
          }
          $formatted = [];
          // Match hoping to split up the relevant digits.
          if (preg_match('/^(\+\d{1,2}\s?)?(\(?\d{3}\)?)[\s.-]?(\d{3})[\s.-]?(\d{4})$/', $phone['number'], $formatted)) {
            array_shift($formatted);
            $number = vsprintf("%s (%s) %s-%s", $formatted);
            // Area code might be empty.
            $phone['number'] = trim(str_replace('()', '', $number));
          }
        }
        $source_data['Person']['phone'] = $phones;
      }
    }
    // Profiles returns a single item, not an array as the Json parser expects.
    return [$source_data];
  }

}
