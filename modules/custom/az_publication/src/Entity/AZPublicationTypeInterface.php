<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Publication Type entities.
 */
interface AZPublicationTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the publication type mapping options.
   *
   * @return array
   *   An array of publication type mapping options.
   */
  public static function getMappableTypeOptions(): array;

  /**
   * Gets the enabled/disabled status of the publication type.
   *
   * @return bool
   *   TRUE if the publication type is enabled, FALSE otherwise.
   */
  public function isEnabled(): bool;

}
