<?php

namespace Drupal\migrate_devel\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Debug the process pipeline.
 *
 * Prints the input value, assuming that you are running the migration from the
 * command line, and sends it to the next step in the pipeline unaltered.
 *
 * Available configuration keys:
 * - label: (optional) a string to print before the debug output. Include any
 *   trailing punctuation or space characters.
 * - multiple: (optional) set to TRUE to ask the next step in the process
 *   pipeline to process array values individually, like the multiple_values
 *   plugin from the Migrate Plus module.
 *
 * Examples:
 *
 * @code
 * process:
 *   field_tricky:
 *     -
 *       plugin: debug
 *       source: whatever
 *     -
 *       plugin: next
 * @endcode
 *
 * This will print the source before passing it to the next plugin.
 *
 * @code
 * process:
 *   field_tricky:
 *     -
 *       plugin: debug
 *       source: whatever
 *       label: 'Step 1: '
 *       multiple: true
 *     -
 *       plugin: next
 * @endcode
 *
 * This does the same thing, but ensures that the next plugin will be called
 * once for each item in the source, if the source is an array.
 * It will also print "Debug Step 1: " before printing the source.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "debug",
 *   handle_multiples = TRUE
 * )
 */
class Debug extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (isset($this->configuration['label'])) {
      dump($this->configuration['label']);
    }
    dump($value);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return !empty($this->configuration['multiple']);
  }

}
