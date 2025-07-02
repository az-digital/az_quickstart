<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines a linkit suggestion with description.
 */
class DescriptionSuggestion extends SimpleSuggestion implements SuggestionDescriptionInterface {

  /**
   * The suggestion description.
   *
   * A string with additional information about the suggestion.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {

    return parent::jsonSerialize() + [
      'description' => $this->getDescription(),
    ];
  }

}
