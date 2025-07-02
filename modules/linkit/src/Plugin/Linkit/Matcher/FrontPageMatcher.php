<?php

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Url;
use Drupal\linkit\MatcherBase;
use Drupal\linkit\Suggestion\DescriptionSuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;

/**
 * Provides specific linkit matchers for the front page.
 *
 * @Matcher(
 *   id = "front_page",
 *   label = @Translation("Front page"),
 * )
 */
class FrontPageMatcher extends MatcherBase {

  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();

    // Special for link to front page.
    if (strpos($string, 'front') !== FALSE) {
      $suggestion = new DescriptionSuggestion();
      $suggestion->setLabel($this->t('Front page'))
        ->setPath(Url::fromRoute('<front>')->toString())
        ->setGroup($this->t('System'))
        ->setDescription($this->t('The front page for this site.'));

      $suggestions->addSuggestion($suggestion);
    }

    return $suggestions;
  }

}
