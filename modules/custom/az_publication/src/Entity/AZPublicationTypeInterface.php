<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Publication Type entities.
 */
interface AZPublicationTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the Publication Type mapping.
   *
   * @return string
   *   The type this is mapped to.
   */
  public function getType():string;

  /**
   * Sets the Publication Type mapping.
   *
   * Validates and sets the mapping property. Each element in the mapping array
   * should be an associative array with a single key-value pair.
   *
   * @param string $type
   *   The mapping string to set.
   * @return $this
   *   The class instance for method chaining.
   */
  public function setType(string $type);

  /**
   * Gets the Publication Type mapping options.
   *
   * @return array
   *   An array of publication type mapping options.
   */
  public static function getTypeOptions(): array;

}
