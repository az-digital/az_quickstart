<?php

declare(strict_types=1);

namespace Drupal\az_publication\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Publication Type entities.
 */
interface AZPublicationTypeInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function load($id): ?AZPublicationTypeInterface;

  /**
   * Gets the publication type mapping options.
   *
   * @return array
   *   An array of publication type mapping options.
   */
  public static function getMappableTypeOptions(): array;

}
