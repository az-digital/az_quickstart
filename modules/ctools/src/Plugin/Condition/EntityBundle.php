<?php

namespace Drupal\ctools\Plugin\Condition;

use Drupal\Core\Entity\Plugin\Condition\EntityBundle as CoreEntityBundle;
use Drupal\ctools\ConstraintConditionInterface;

/**
 * Entity Bundle Constraints
 *
 * Adds constraints to Drupal\Core\Entity\Plugin\Condition\EntityBundle.
 */
class EntityBundle extends CoreEntityBundle implements ConstraintConditionInterface {

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   */
  public function applyConstraints(array $contexts = []) {
    // Nullify any bundle constraints on contexts we care about.
    $this->removeConstraints($contexts);
    $bundle = array_values($this->configuration['bundles']);
    // There's only one expected context for this plugin type.
    foreach ($this->getContextMapping() as $definition_id => $context_id) {
      $contexts[$context_id]->getContextDefinition()->addConstraint('Bundle', ['value' => $bundle]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   */
  public function removeConstraints(array $contexts = []) {
    // Reset the bundle constraint for any context we've mapped.
    foreach ($this->getContextMapping() as $definition_id => $context_id) {
      $constraints = $contexts[$context_id]->getContextDefinition()->getConstraints();
      unset($constraints['Bundle']);
      $contexts[$context_id]->getContextDefinition()->setConstraints($constraints);
    }
  }
}