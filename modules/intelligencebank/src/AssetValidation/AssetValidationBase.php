<?php

namespace Drupal\ib_dam\AssetValidation;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Asset\LocalAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Base implementation for asset validation plugins.
 */
abstract class AssetValidationBase extends PluginBase implements AssetValidationInterface, ContainerFactoryPluginInterface {

  /**
   * Plugin label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Typed Data Manager service.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TypedDataManagerInterface $typed_data_manager) {
    $plugin_definition += [
      'constraint' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $assets, array $options = [], $use_asset_validators = TRUE) {
    $final_violations = new ConstraintViolationList();

    /* @var \Drupal\ib_dam\Asset\LocalAsset $asset */
    foreach ($assets as $asset) {
      $validators = $options['validators'];
      $violations = new ConstraintViolationList();

      if ($use_asset_validators) {
        $validator_ids = $asset::getApplicableValidators();
        $validators = array_intersect_key($validators, array_flip($validator_ids));
      }
      foreach ($validators as $validator_function => $validator_options) {
        if (method_exists($this, $validator_function)) {
          $errors = $this->$validator_function($asset, $validator_options);
          foreach ($errors as $error) {

            $violation = new ConstraintViolation(
              $error,
              $error,
              [],
              $asset,
              '',
              $asset
            );
            $violations->add($violation);
          };
        }

      }
      if ($violations->count() > 0) {
        $this->aggregateViolations($final_violations, $violations, $asset);
      }
    }

    return $final_violations;
  }

  /**
   * Helper method to wrap all violations into one "context".
   */
  protected function aggregateViolations(ConstraintViolationList &$final_violations, ConstraintViolationList $violations, AssetInterface $asset) {
    if ($asset instanceof LocalAsset) {
      $validation_error = $this->t('The specified file %name could not be uploaded. <br>Reasons:', [
        '%name' => $asset->localFile()->getFilename(),
      ]);
    }
    else {
      $validation_error = $this->t('The specified asset %name could not be uploaded. <br>Reasons:', [
        '%name' => $asset->getName(),
      ]);
    }

    $common_violation = new ConstraintViolation(
      $validation_error,
      $validation_error,
      [],
      $asset,
      '',
      $asset
    );
    $final_violations->add($common_violation);
    $final_violations->addAll($violations);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Gets a data definition and optionally adds a constraint.
   *
   * @param string $data_type
   *   The data type plugin ID, for which a constraint should be added.
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array $options
   *   Array of options needed by the constraint validator.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition object for the given data type.
   */
  protected function getDataDefinition($data_type, $constraint_name = NULL, array $options = []) {
    $data_definition = $this->typedDataManager->createDataDefinition($data_type);
    if ($constraint_name) {
      $data_definition->addConstraint($constraint_name, $options);
    }
    return $data_definition;
  }

  /**
   * Creates and validates instances of typed data for each Entity.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   *   The data definition generated from ::getDataDefinition().
   * @param array $entities
   *   An array of Entities to validate the definition against.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationListInterface
   *   A list of violations.
   */
  protected function validateDataDefinition(DataDefinitionInterface $data_definition, array $entities) {
    $violations = new ConstraintViolationList();
    foreach ($entities as $entity) {
      $validation_result = $this->typedDataManager->create($data_definition, $entity)->validate();
      $violations->addAll($validation_result);
    }

    return $violations;
  }

}
