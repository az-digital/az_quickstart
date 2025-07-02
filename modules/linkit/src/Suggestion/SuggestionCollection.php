<?php

namespace Drupal\linkit\Suggestion;

/**
 * Defines a suggestion collection used to avoid JSON Hijacking.
 */
class SuggestionCollection implements \JsonSerializable {

  /**
   * An array of suggestions.
   *
   * @var array
   */
  protected $suggestions = [];

  /**
   * Returns all suggestions in the collection.
   *
   * @return \Drupal\linkit\Suggestion\SuggestionInterface[]
   *   All suggestions in the collection.
   */
  public function getSuggestions() {
    return $this->suggestions;
  }

  /**
   * Adds a suggestion to this collection.
   *
   * @param \Drupal\linkit\Suggestion\SuggestionInterface $suggestion
   *   The suggestion to add to the collection.
   */
  public function addSuggestion(SuggestionInterface $suggestion) {
    $this->suggestions[] = $suggestion;
  }

  /**
   * Adds a collection of suggestions to the this collection.
   *
   * @param \Drupal\linkit\Suggestion\SuggestionCollection $suggestionCollection
   *   A collection of suggestions.
   */
  public function addSuggestions(SuggestionCollection $suggestionCollection) {
    $this->suggestions = array_merge($this->suggestions, $suggestionCollection->getSuggestions());
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return [
      'suggestions' => $this->suggestions,
    ];
  }

}
