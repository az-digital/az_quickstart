<?php

namespace Drupal\entity_reference_revisions;

/**
 * Allows an entity to define whether it needs to be saved.
 */
interface EntityNeedsSaveInterface {

  /**
   * Checks whether the entity needs to be saved.
   *
   * @return bool
   *   TRUE if the entity needs to be saved.
   */
  public function needsSave();
  
  /**
   * Sets the "needs save" flag on an entity.
   *
   * @param bool $needs_save
   *   TRUE if the entity needs to be saved.
   *
   * @return void
   */
  public function setNeedsSave(bool $needs_save);  
}
