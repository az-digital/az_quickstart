<?php

namespace Drupal\ib_dam\AssetValidation;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Class AssetViolationAggregator.
 *
 * Aggregates constraint violations into one list.
 *
 * @package Drupal\ib_dam\AssetValidation
 */
class AssetViolationAggregator {

  /**
   * Extract messages from violations list.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   The violations list.
   * @param string $mode
   *   Possible options:
   *     - 'markup': render list of violation messages as html list,
   *     - 'simple': just return list of pairs
   *       of the violation message and parameters.
   *
   * @return array
   *   The violation messages list.
   */
  public static function extractMessages(ConstraintViolationListInterface $violations, $mode = 'markup') {
    $errors = [];

    /* @var $violation \Symfony\Component\Validator\ConstraintViolationInterface */
    foreach ($violations as $violation) {
      $errors[] = $mode === 'markup'
        ? ['#markup' => $violation->getMessage()]
        : [$violation->getMessage(), $violation->getParameters()];
    }

    if (!empty($errors) && $mode === 'markup') {
      $error_list = [
        'item_list' => ['#theme' => 'item_list', '#items' => $errors],
      ];

      return \Drupal::service('renderer')->renderInIsolation($error_list);
    }

    return $errors;
  }

}
