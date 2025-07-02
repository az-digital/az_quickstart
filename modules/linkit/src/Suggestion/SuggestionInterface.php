<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines the interface for suggestions.
 */
interface SuggestionInterface extends \JsonSerializable {

  /**
   * Gets the suggestion label.
   *
   * @return string
   *   The suggestion label.
   */
  public function getLabel();

  /**
   * Sets the suggestion label.
   *
   * @param string $label
   *   The suggestion label to set.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the suggestion path.
   *
   * @return string
   *   The suggestion path.
   */
  public function getPath();

  /**
   * Sets the suggestion path.
   *
   * @param string $path
   *   The suggestion path to set.
   *
   * @return $this
   */
  public function setPath($path);

  /**
   * Gets the suggestion status.
   *
   * @return string
   *   The suggestion status.
   */
  public function getStatus();

  /**
   * Sets the suggestion status.
   *
   * @param string $status
   *   The suggestion status to set.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Gets the suggestion group.
   *
   * @return string
   *   The suggestion group.
   */
  public function getGroup();

  /**
   * Sets the suggestion group.
   *
   * @param string $group
   *   The suggestion group to set.
   *
   * @return $this
   */
  public function setGroup($group);

}
