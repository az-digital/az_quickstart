<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * SQL table source plugin.
 *
 * Available configuration keys:
 * - table_name: The base table name.
 * - id_fields: Fields used by migrate to identify table rows uniquely. At least
 *   one field is required.
 * - fields: (optional) An indexed array of columns present in the source table.
 *   Leave empty to retrieve all columns.
 *
 * Examples:
 *
 * @code
 *   source:
 *     plugin: table
 *     table_name: colors
 *     id_fields:
 *       color_name:
 *         type: string
 *       hex:
 *         type: string
 *     fields:
 *       color_name: color_name
 *       hex: hex
 * @endcode
 *
 * In this example color data is retrieved from the source table.
 *
 * @code
 *   source:
 *     plugin: table
 *     table_name: autoban
 *     id_fields:
 *       type:
 *         type: string
 *       message:
 *         type: string
 *       threshold:
 *         type: integer
 *       user_type:
 *         type: integer
 *       ip_type:
 *         type: integer
 *       referer:
 *         type: string
 *     fields:
 *       type: type
 *       message: message
 *       threshold: threshold
 *       user_type: user_type
 *       ip_type: ip_type
 *       referer: referer
 * @endcode
 *
 * In this example shows how to retrieve data from autoban source table.
 *
 * For additional configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 *
 * @MigrateSource(
 *   id = "table"
 * )
 */
class Table extends SqlBase {

  /**
   * Table alias.
   *
   * @var string
   */
  public const TABLE_ALIAS = 't';

  /**
   * The name of the destination table.
   *
   * @var string
   */
  protected string $tableName;

  /**
   * IDMap compatible array of id fields.
   *
   * @var array
   */
  protected array $idFields;

  /**
   * Array of fields present on the destination table.
   *
   * @var array
   */
  protected array $fields;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    if (empty($configuration['table_name'])) {
      throw new \InvalidArgumentException('Table plugin is missing table_name property configuration.');
    }
    if (!array_key_exists('id_fields', $configuration)) {
      throw new \InvalidArgumentException('Table plugin is missing id_fields property configuration.');
    }
    if (!is_array($configuration['id_fields'])) {
      throw new \InvalidArgumentException('Table plugin configuration property id_fields must be an array.');
    }
    if (array_key_exists('fields', $configuration) and !is_array($configuration['fields'])) {
      throw new \InvalidArgumentException('Table plugin configuration property fields must be an array.');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->tableName = $configuration['table_name'];
    // Insert alias in id_fields.
    foreach ($configuration['id_fields'] as &$field) {
      $field['alias'] = static::TABLE_ALIAS;
    }
    $this->idFields = $configuration['id_fields'];
    $this->fields = $configuration['fields'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select($this->tableName, static::TABLE_ALIAS)->fields(static::TABLE_ALIAS, $this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return $this->idFields;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements(): void {
    if (!$this->getDatabase()->schema()->tableExists($this->tableName)) {
      throw new RequirementsException("Source database table '{$this->tableName}' does not exist", ['source_table' => $this->tableName]);
    }
    parent::checkRequirements();
  }

}
