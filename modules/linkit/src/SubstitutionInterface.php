<?php

namespace Drupal\linkit;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Interface for substitution plugins.
 */
interface SubstitutionInterface extends PluginInspectionInterface {

  /**
   * Get the URL associated with a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get a URL for.
   *
   * @return \Drupal\Core\Url
   *   A URL to replace.
   */
  public function getUrl(EntityInterface $entity);

  /**
   * Checks if this substitution plugin is applicable for the given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type object.
   *
   * @return bool
   *   If the plugin is applicable.
   */
  public static function isApplicable(EntityTypeInterface $entity_type);

}
