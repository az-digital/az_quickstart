<?php

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Complex data constraint.
 *
 * Validates properties of complex data structures.
 */
#[Constraint(
  id: 'ComplexData',
  label: new TranslatableMarkup('Complex data', [], ['context' => 'Validation'])
)]
class ComplexDataConstraint extends SymfonyConstraint {

  /**
   * An array of constraints for contained properties, keyed by property name.
   *
   * @var array
   */
  public $properties;

  /**
   * {@inheritdoc}
   */
  public function __construct($options = NULL) {
    // Allow skipping the 'properties' key in the options.
    if (is_array($options) && !array_key_exists('properties', $options)) {
      $options = ['properties' => $options];
    }
    parent::__construct($options);
    $constraint_manager = \Drupal::service('validation.constraint');

    // Instantiate constraint objects for array definitions.
    foreach ($this->properties as &$constraints) {
      foreach ($constraints as $id => $options) {
        if (!is_object($options)) {
          $constraints[$id] = $constraint_manager->create($id, $options);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @return ?string
   *   Name of the default option.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function getDefaultOption() {
    return 'properties';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The names of the required options.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function getRequiredOptions() {
    return ['properties'];
  }

}
