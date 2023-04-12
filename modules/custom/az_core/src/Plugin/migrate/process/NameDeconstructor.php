<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use TheIconic\NameParser\Parser;

/**
 * Process Plugin to break full name strings into parts.
 *
 * Expects a string containing a full name of a person.
 *
 * Available configuration keys
 * - sourcet: (required) Field to copy name from.
 *
 * Returns an associative array with the following structure.
 * @code
 * [
 *   [nickname] =>
 *   [salutation] => Mr.
 *   [firstname] => Anthony
 *   [initials] => R
 *   [lastname] => Von Fange
 *   [suffix] => III
 *   [full] => Mr. Anthony R Von Fange III
 *   [given] => Anthony R
 * ]
 *
 * Consider migrating taxonomy terms using the name field (entity label) for names.
 * @code
 *   process:
 *     psuedo_deconstruct_name:
 *     - plugin: az_name_deconstruct
 *       source: name
 *     field_az_author_lname:'@psuedo_deconstruct_name/lname'
 *     field_az_author_fname:'@psuedo_deconstruct_name/fname'
 *     field_az_author_suffix:'@psuedo_deconstruct_name/suffix'
 *
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "az_name_deconstruct",
 * )
 */
class NameDeconstructor extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (empty($this->configuration['source'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'source' is required."
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $parser = new Parser();
    $name = $parser->parse($value);
    $value = [
      'nickname' => $name->getNickname(),
      'salutation' => $name->getSalutation(),
      'firstname' => $name->getFirstname(),
      'initials' => $name->getInitials(),
      'lastname' => $name->getLastname(),
      'suffix' => $name->getSuffix(),
      'middlename' => $name->getMiddlename(),
      'full' => $name->getFullName(),
      'given' => $name->getGivenName(),
    ];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
