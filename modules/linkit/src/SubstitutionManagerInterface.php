<?php

namespace Drupal\linkit;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * An interface for the substitution manager.
 */
interface SubstitutionManagerInterface extends PluginManagerInterface {

  /**
   * Get the default substitution.
   */
  const DEFAULT_SUBSTITUTION = 'canonical';

  /**
   * Get a form API options list for the entity ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An options list.
   */
  public function getApplicablePluginsOptionList($entity_type_id);

}
