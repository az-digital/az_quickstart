<?php

namespace Drupal\ctools;

/**
 * Interface for Constraint Conditions
 */
interface ConstraintConditionInterface {

  /**
   * Applies relevant constraints for this condition to the injected contexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Contexts to apply.
   */
  public function applyConstraints(array $contexts = []);

  /**
   * Removes constraints for this condition from the injected contexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   Contexts to remove.
   */
  public function removeConstraints(array $contexts = []);

}
