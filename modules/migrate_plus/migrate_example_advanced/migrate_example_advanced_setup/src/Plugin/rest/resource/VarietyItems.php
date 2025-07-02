<?php

declare(strict_types = 1);

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_items",
 *   label = @Translation("Advanced migration example - Variety data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_list/{variety}"
 *   }
 * )
 */
final class VarietyItems extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   * @param string|null $variety
   *   Machine name of the variety to retrieve.
   *
   *   The response containing the requested variety data.
   */
  public function get(?string $variety = NULL): ResourceResponse {
    $varieties = [
      'retsina' => [
        'name' => 'Retsina',
        // The categoryid for 'white'.
        'parent' => 1,
        'details' => 'Greek',
      ],
      'trebbiano' => [
        'name' => 'Trebbiano',
        // The categoryid for 'white'.
        'parent' => 1,
        'details' => 'Italian',
      ],
      'valpolicella' => [
        'name' => 'Valpolicella',
        // The categoryid for 'red'.
        'parent' => 3,
        'details' => 'Italian Venoto region',
      ],
      'bardolino' => [
        'name' => 'Bardolino',
        // The categoryid for 'red'.
        'parent' => 3,
        'details' => 'Italian Venoto region',
      ],
    ];
    if (isset($varieties[$variety])) {
      $data = ['variety' => $varieties[$variety]];
    }
    else {
      $data = [];
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
