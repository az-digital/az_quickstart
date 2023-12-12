<?php

declare(strict_types=1);

namespace Drupal\az_publication\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
* Ensures valid publication type mapping.
*
* @Constraint(
* id = "ValidPublicationMapping",
* label = @Translation("Valid Publication Type", context = "Validation"),
* )
*/
class ValidPublicationMappingConstraint extends Constraint {
  public $message = 'The type "%type" is not a valid mapping.';
}
