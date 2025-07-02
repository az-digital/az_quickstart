<?php

declare(strict_types = 1);

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides varieties as two endpoints, one for reds and one for whites.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_variety_list",
 *   label = @Translation("Advanced migration example - Variety list of data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_variety_list"
 *   }
 * )
 */
final class VarietyList extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   *   The response containing the requested variety data.
   */
  public function get(): ResourceResponse {
    $data = [];
    $data['items'] = ['retsina', 'trebbiano', 'valpolicella', 'bardolino'];

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
