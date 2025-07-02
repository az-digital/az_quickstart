<?php

namespace Drupal\ib_dam\AssetValidation;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for asset validations.
 */
interface AssetValidationInterface extends PluginInspectionInterface, ConfigurableInterface {

  /**
   * Returns the widget validation label.
   *
   * @return string
   *   The widget validation label.
   */
  public function label();

  /**
   * Validates the asset.
   *
   * Collect available validators and run validation process.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface[] $assets
   *   Array of selected assets.
   * @param array $options
   *   (Optional) Array of options needed by the constraint validator.
   * @param bool $use_asset_validators
   *   (Optional) Use validators that declared in asset class,
   *   in other case run all available validators.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate(array $assets, array $options = [], $use_asset_validators = TRUE);

}
