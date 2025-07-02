<?php

declare(strict_types = 1);

namespace Drupal\migrate_example_advanced_setup\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Represents positions as resources.
 *
 * @RestResource(
 *   id = "migrate_example_advanced_position",
 *   label = @Translation("Advanced migration example - Position data"),
 *   uri_paths = {
 *     "canonical" = "/migrate_example_advanced_position"
 *   }
 * )
 */
final class PositionResource extends ResourceBase {

  /**
   * Responds to GET requests.
   *
   *   The response containing the position data.
   */
  public function get(): ResourceResponse {
    $position1 = ['sourceid' => 'wine_taster', 'name' => 'Wine Taster'];
    $position2 = ['sourceid' => 'vintner', 'name' => 'Vintner'];
    $data = ['position' => [$position1, $position2]];

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
