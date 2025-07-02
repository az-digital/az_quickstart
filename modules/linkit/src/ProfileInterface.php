<?php

namespace Drupal\linkit;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a profile entity.
 */
interface ProfileInterface extends ConfigEntityInterface {

  /**
   * Gets the profile description.
   *
   * @return string
   *   The profile description.
   */
  public function getDescription();

  /**
   * Sets the profile description.
   *
   * @param string $description
   *   The profile description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns a specific matcher.
   *
   * @param string $instance_id
   *   The matcher instance ID.
   *
   * @return \Drupal\linkit\MatcherInterface
   *   The matcher object.
   */
  public function getMatcher($instance_id);

  /**
   * Returns the first enabled matcher for the given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher|null
   *   An entity matcher instance or null if not found.
   */
  public function getMatcherByEntityType($entity_type_id);

  /**
   * Returns the matchers for this profile.
   *
   * @return \Drupal\linkit\MatcherCollection|\Drupal\linkit\MatcherInterface[]
   *   The matcher collection.
   */
  public function getMatchers();

  /**
   * Adds a matcher to this profile.
   *
   * @param array $configuration
   *   An array of matcher configuration.
   *
   * @return string
   *   The instance ID of the matcher.
   */
  public function addMatcher(array $configuration);

  /**
   * Removes a matcher from this profile.
   *
   * @param \Drupal\linkit\MatcherInterface $matcher
   *   The matcher object.
   *
   * @return $this
   */
  public function removeMatcher(MatcherInterface $matcher);

  /**
   * Sets the configuration for a matcher instance.
   *
   * @param string $instance_id
   *   The instance ID of the matcher to set the configuration for.
   * @param array $configuration
   *   The matcher configuration to set.
   *
   * @return $this
   */
  public function setMatcherConfig($instance_id, array $configuration);

}
