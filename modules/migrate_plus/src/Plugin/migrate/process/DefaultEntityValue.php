<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Returns EntityLookup for a given default value if input is empty.
 *
 * Available configuration keys:
 * - default_value: The default value that will be used as for the entity lookup.
 * For additional configuration keys, refer to the parent class.
 *
 * Example:
 * @code
 * process:
 *   uid:
 *     -
 *       plugin: migration_lookup
 *       migration: users
 *       source: author
 *     -
 *       plugin: default_entity_value
 *       entity_type: user
 *       value_key: name
 *       ignore_case: true
 *       default_value: editorial
 * @endcode
 *
 * In this example, it will look up the source value of author in the users
 * migration and if not found, use entity lookup to find a user with "editorial"
 * username.
 *
 * @see \Drupal\migrate_plus\Plugin\migrate\process\EntityLookup
 *
 * @MigrateProcessPlugin(
 *   id = "default_entity_value",
 *   handle_multiples = TRUE
 * )
 */
class DefaultEntityValue extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($value)) {
      return $value;
    }
    return parent::transform($this->configuration['default_value'], $migrate_executable, $row, $destination_property);
  }

}
