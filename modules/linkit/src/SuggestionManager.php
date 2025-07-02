<?php

namespace Drupal\linkit;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Suggestion service to handle autocomplete suggestions.
 */
class SuggestionManager {

  use StringTranslationTrait;

  /**
   * Gets the suggestions.
   *
   * @param ProfileInterface $linkitProfile
   *   The linkit profile.
   * @param string $search_string
   *   The string ro use in the matchers.
   *
   * @return \Drupal\linkit\Suggestion\SuggestionCollection
   *   A suggestion collection.
   */
  public function getSuggestions(ProfileInterface $linkitProfile, $search_string) {
    $suggestions = new SuggestionCollection();

    if (empty(trim($search_string))) {
      return $suggestions;
    }

    foreach ($linkitProfile->getMatchers() as $plugin) {
      $suggestions->addSuggestions($plugin->execute($search_string));
    }

    return $suggestions;
  }

  /**
   * Adds an unscathed suggestion to the given suggestion collection.
   *
   * @param \Drupal\linkit\Suggestion\SuggestionCollection $suggestionCollection
   *   A suggestion collection to add the unscathed suggestion to.
   * @param string $search_string
   *   The string ro use in the matchers.
   *
   * @return \Drupal\linkit\Suggestion\SuggestionCollection
   *   A suggestion collection.
   */
  public function addUnscathedSuggestion(SuggestionCollection $suggestionCollection, $search_string) {
    $suggestion = new DescriptionSuggestion();
    $suggestion->setLabel(Html::escape($search_string))
      ->setGroup($this->t('No results'))
      ->setDescription($this->t('No content suggestions found. This URL will be used as is.'))
      ->setPath($search_string);
    $suggestionCollection->addSuggestion($suggestion);
    return $suggestionCollection;
  }

}
