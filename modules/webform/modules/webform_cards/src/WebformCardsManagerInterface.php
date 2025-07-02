<?php

namespace Drupal\webform_cards;

use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Interface for webform cards manager.
 */
interface WebformCardsManagerInterface {

  /**
   * Determine if a webform has cards.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return bool
   *   TRUE if a webform has cards.
   */
  public function hasCards(WebformInterface $webform);

  /**
   * Counts the number of cards used in a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int
   *   The number of cards for the webform.
   */
  public function getNumberOfCards(WebformInterface $webform);

  /**
   * Build webform's cards based on the current operation.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $operation
   *   The webform submission operation.
   *   Usually 'default', 'add', 'edit', 'edit_all', 'api', or 'test'.
   *
   * @return array
   *   An associative array of webform cards.
   *
   * @see \Drupal\webform\Entity\Webform::buildPages
   */
  public function buildPages(WebformInterface $webform, $operation = 'default');

  /**
   * Update cards pages based on conditional logic (#states).
   *
   * @param array $pages
   *   An associative array of webform cards.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission.
   *
   * @return array
   *   An associative array of webform cards with conditional logic applied.
   *
   * @see \Drupal\webform\Entity\Webform::getPages
   */
  public function applyConditions(array $pages, WebformSubmissionInterface $webform_submission = NULL);

}
