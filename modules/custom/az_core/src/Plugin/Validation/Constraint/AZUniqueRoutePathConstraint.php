<?php

namespace Drupal\az_core\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for unique route paths.
 */
#[Constraint(
  id: 'AZUniqueRoutePath',
  label: new TranslatableMarkup('Unique route path.', [], ['context' => 'Validation'])
)]
class AZUniqueRoutePathConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = "The path is already in use.";

}
