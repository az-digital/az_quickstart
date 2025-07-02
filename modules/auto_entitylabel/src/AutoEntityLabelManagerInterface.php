<?php

namespace Drupal\auto_entitylabel;

/**
 * Provides an interface for AutoEntityLabelManager.
 */
interface AutoEntityLabelManagerInterface {

  /**
   * Sets the automatically generated entity label.
   *
   * @return string
   *   The applied label. The entity is updated with this label.
   */
  public function setLabel();

  /**
   * Determines if the entity bundle has auto entity label enabled.
   *
   * @return bool
   *   True if the entity bundle has an automatic label.
   */
  public function hasAutoLabel();

  /**
   * Determines if the entity bundle has an optional automatic label.
   *
   * Optional means that if the label is empty, it will be automatically
   * generated.
   *
   * @return bool
   *   True if the entity bundle has an optional automatic label.
   */
  public function hasOptionalAutoLabel();

  /**
   * Returns whether the automatic label has to be set.
   *
   * @return bool
   *   Returns true if the label should be automatically generated.
   */
  public function autoLabelNeeded();

  /**
   * Returns status of automatic entity label.
   *
   * @return bool
   *   Status value.
   */
  public function getStatus();

  /**
   * Returns pattern for the label.
   *
   * @return string
   *   Pattern value.
   */
  public function getPattern();

  /**
   * Returns the action to take on new entities.
   *
   * @return int
   *   The new entity action value.
   */
  public function getNewContentBehavior();

}
