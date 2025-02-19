<?php

declare(strict_types=1);

namespace Drupal\az_enterprise_attributes_import\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Flattens a multi-dimensional array with enterprise attributes in it.
 *
 * @code
 * process:
 *   field_of_array_values:
 *   - plugin: az_enterprise_attributes_flatten
 *     source: enterprise_attributes_array
 *   - plugin: flatten
 * @endcode
 */
#[MigrateProcess('az_enterprise_attributes_flatten')]
class AZEnterpriseAttributesArrayFlatten extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Cached CSV exceptions to avoid repeated lookups.
   *
   * @var array|null
   */
  protected ?array $csv_exceptions = NULL;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the process plugin.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get CSV Exceptions by searching for terms with a comma in the name.
   *
   * @param array $vocabulary_machine_names
   *   The machine names of vocabularies to search for terms with a comma in the name.
   *
   * @return array
   *   An array of taxonomy term names containing a comma.
   */
  public function getCSVExceptions(array $vocabulary_machine_names): array {
    if ($this->csv_exceptions !== NULL) {
      return $this->csv_exceptions;
    }

    $csv_exceptions = [];

    foreach ($vocabulary_machine_names as $vocabulary_machine_name) {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vocabulary_machine_name]);
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        $term_name = $term->getName();
        // If term name contains a comma, add it to exceptions.
        if (strpos($term_name, ',') !== FALSE) {
          $csv_exceptions[] = $term_name;
        }
      }
    }

    $this->csv_exceptions = $csv_exceptions;
    return $csv_exceptions;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($input, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($input)) {
      return $input;
    }

    $result = [];
    $csv_exceptions = array_flip($this->getCSVExceptions(['az_enterprise_attributes']));
    foreach ($input as $value) {
      if (is_array($value) && isset($value[0]) && is_string($value[0])) {
        $string = trim($value[0]);

        // Preserve known exceptions, otherwise split by commas.
        if (isset($csv_exceptions[$string])) {
          $result[] = $string;
        }
        else {
          $result = array_merge($result, array_map('trim', explode(',', $string)));
        }
      }
    }

    return array_values(array_unique($result));
  }

}
