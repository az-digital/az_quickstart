<?php

namespace Drupal\linkit_test\Plugin\Linkit\Matcher;

use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\SimpleSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides test linkit matchers for the dummy_matcher entity type.
 *
 * @Matcher(
 *   id = "dummy_matcher",
 *   label = @Translation("Dummy Matcher"),
 * )
 */
class DummyMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $suggestion = new SimpleSuggestion();
    $suggestion->setLabel('Dummy Matcher title')
      ->setPath('http://example.com')
      ->setGroup('Dummy Matcher');

    $suggestions->addSuggestion($suggestion);

    return $suggestions;
  }

}
