<?php

namespace Drupal\linkit;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for matchers.
 *
 * @see \Drupal\linkit\Annotation\Matcher
 * @see \Drupal\linkit\MatcherBase
 * @see \Drupal\linkit\MatcherManager
 * @see plugin_api
 */
interface MatcherInterface extends PluginInspectionInterface, ConfigurableInterface, DependentPluginInterface {

  /**
   * Returns the unique ID representing the matcher.
   *
   * @return string
   *   The matcher ID.
   */
  public function getUuid();

  /**
   * Returns the matcher label.
   *
   * @return string
   *   The matcher label.
   */
  public function getLabel();

  /**
   * Returns the summarized configuration of the matcher.
   *
   * @return array
   *   An array of summarized configuration of the matcher.
   */
  public function getSummary();

  /**
   * Returns the weight of the matcher.
   *
   * @return int|string
   *   Either the integer weight of the matcher, or an empty string.
   */
  public function getWeight();

  /**
   * Sets the weight for the matcher.
   *
   * @param int $weight
   *   The weight for this matcher.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Executes the matcher.
   *
   * @param string $string
   *   The string that contains the text to search for.
   *
   * @return \Drupal\linkit\Suggestion\SuggestionCollection
   *   A suggestion collection.
   */
  public function execute($string);

}
