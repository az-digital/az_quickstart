<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines the interface for suggestions that have a description.
 */
interface SuggestionDescriptionInterface {

  /**
   * Gets the suggestion description.
   *
   * @return string
   *   The suggestion description.
   */
  public function getDescription();

  /**
   * Sets the suggestion description.
   *
   * @param string $description
   *   The suggestion description.
   *
   * @return $this
   */
  public function setDescription($description);

}
