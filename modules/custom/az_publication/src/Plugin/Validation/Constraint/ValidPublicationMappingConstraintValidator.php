<?php

declare(strict_types=1);

namespace Drupal\az_publication\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidPublicationMappingConstraintValidator extends ConstraintValidator {

  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      // Assuming getMappingOptions returns an array of valid mappings
      $validMappings = AZPublicationType::getTypeOptions();
      if (!isset($validMappings[$item->value])) {
        $this->context->buildViolation($constraint->message, ['%type' => $item->value])->addViolation();
      }
    }
  }
}
