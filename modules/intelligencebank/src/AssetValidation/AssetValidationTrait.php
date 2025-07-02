<?php

namespace Drupal\ib_dam\AssetValidation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ib_dam\Exceptions\AssetValidationBadPluginId;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Trait AssetValidationTrait.
 *
 * Run asset validation process.
 *
 * @package Drupal\ib_dam\AssetValidation
 */
trait AssetValidationTrait {

  /**
   * Validate assets and mark form as dirty with errors.
   *
   * @param array $validators
   *   The validators list.
   * @param array $assets
   *   The assets list.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state to mark form as invalid.
   * @param array &$element
   *   The reference to the ib_dam browser element.
   */
  public function validateAssets(
    array $validators,
    array $assets,
    FormStateInterface $form_state,
    array &$element
  ) {

    $violations = $this->runAssetValidators($assets, $validators);
    $messages   = AssetViolationAggregator::extractMessages($violations);

    if ($messages) {
      $form_state->setError($element, $messages);
    }
  }

  /**
   * Get AssetValidationManager service.
   *
   * @return \Drupal\ib_dam\AssetValidation\AssetValidationManager
   *   The service instance.
   */
  abstract protected function getAssetValidationManager();

  /**
   * Defines asset validators and runs them.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface[] $assets
   *   The list of assets to check over.
   * @param array $validators
   *   The list of validator.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   *   List of validations violations.
   */
  protected function runAssetValidators(array $assets, array $validators) {
    $violations = new ConstraintViolationList();

    foreach ($validators as $options) {
      /** @var \Drupal\ib_dam\AssetValidation\AssetValidationInterface $validator */
      try {
        $validator = $this->getAssetValidationManager()->getInstance($options);
      }
      catch (AssetValidationBadPluginId $e) {
        $e->logException();
        continue;
      }

      if ($validator) {
        $val = $validator->validate($assets, $options);
        $violations->addAll($val);
      }
    }
    return $violations;
  }

}
