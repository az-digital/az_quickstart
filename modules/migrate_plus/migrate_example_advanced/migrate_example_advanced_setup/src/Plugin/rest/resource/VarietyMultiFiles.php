<?php

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_multiple",
 *   label = @Translation("Advanced migration example - Variety data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_multiple/{type}"
 *   }
 * )
 */
class VarietyMultiFiles extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @param string|null $type
   *   'red', 'white', or NULL to return all varieties.
   *
   *   The response containing the requested variety data.
   */
  public function get(?string $type = NULL): ResourceResponse {
    $data = [];
    if (strtolower($type) != 'white') {
      $data['variety'][] = [
        'name' => 'Amarone',
        // The categoryid for 'red'.
        'parent' => 3,
        'details' => 'Italian Venoto region',
        'attributes' => [
          'rich',
          'aromatic',
        ],
      ];
      $data['variety'][] = [
        'name' => 'Barbaresco',
        // The categoryid for 'red'.
        'parent' => 3,
        'details' => 'Italian Piedmont region',
        'attributes' => [
          'smoky',
          'earthy',
        ],
      ];
    }
    if (strtolower($type) != 'red') {
      $data['variety'][] = [
        'name' => 'Kir',
        // The categoryid for 'white'.
        'parent' => 1,
        'details' => 'French Burgundy region',
        'attributes' => [],
      ];
      $data['variety'][] = [
        'name' => 'Pinot Grigio',
        // The categoryid for 'white'.
        'parent' => 1,
        'details' => 'From the northeast of Italy',
        'attributes' => [
          'fruity',
          'medium-bodied',
          'slightly sweet',
        ],
      ];
    }

    return new ResourceResponse($data, 200);
  }

  /**
   * {@inheritdoc}
   */
  public function permissions(): array {
    // Remove permissions so the resource is available to all.
    return [];
  }

}
