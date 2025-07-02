<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Constraint on entity level.
 */
#[Constraint(
  id: 'EntityTestEntityLevel',
  label: new TranslatableMarkup('Constraint on the entity level.'),
  type: ['entity']
)]
class EntityTestEntityLevel extends SymfonyConstraint {

  public $message = 'Entity level validation';

}
