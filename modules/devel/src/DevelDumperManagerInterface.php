<?php

namespace Drupal\devel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Interface for DevelDumper manager.
 *
 * @package Drupal\devel
 */
interface DevelDumperManagerInterface {

  /**
   * Dumps information about a variable.
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param string $plugin_id
   *   (optional) The plugin ID, defaults to NULL.
   */
  public function dump(mixed $input, $name = NULL, $plugin_id = NULL);

  /**
   * Returns a string representation of a variable.
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string|null $name
   *   (optional) The label to output before variable.
   * @param string|null $plugin_id
   *   (optional) The plugin ID, defaults to NULL.
   * @param bool $load_references
   *   If the input is an entity, load the referenced entities.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   String representation of a variable.
   */
  public function export(mixed $input, ?string $name = NULL, ?string $plugin_id = NULL, bool $load_references = FALSE): MarkupInterface|string;

  /**
   * Sets a message with a string representation of a variable.
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string $name
   *   The label to output before variable.
   * @param string $type
   *   (optional) The message's type. Defaults to
   *   MessengerInterface::TYPE_STATUS.
   * @param string $plugin_id
   *   (optional) The plugin ID. Defaults to NULL.
   * @param bool $load_references
   *   (optional) If the input is an entity, load the referenced entities.
   *   Defaults to FALSE.
   */
  public function message(mixed $input, $name = NULL, $type = MessengerInterface::TYPE_STATUS, $plugin_id = NULL, $load_references = FALSE);

  /**
   * Logs a variable to a drupal_debug.txt in the site's temp directory.
   *
   * @param mixed $input
   *   The variable to log to the drupal_debug.txt log file.
   * @param string $name
   *   (optional) If set, a label to output before $data in the log file.
   * @param string $plugin_id
   *   (optional) The plugin ID, defaults to NULL.
   *
   * @return void|false
   *   Empty if successful, FALSE if the log file could not be written.
   *
   * @see dd()
   * @see http://drupal.org/node/314112
   */
  public function debug(mixed $input, $name = NULL, $plugin_id = NULL);

  /**
   * Wrapper for ::dump() and ::export().
   *
   * @param mixed $input
   *   The variable to dump.
   * @param string $name
   *   (optional) The label to output before variable, defaults to NULL.
   * @param bool $export
   *   (optional) Whether return string representation of a variable.
   * @param string $plugin_id
   *   (optional) The plugin ID, defaults to NULL.
   *
   * @return string|null
   *   String representation of a variable if $export is set to TRUE,
   *   NULL otherwise.
   */
  public function dumpOrExport(mixed $input, $name = NULL, $export = TRUE, $plugin_id = NULL);

  /**
   * Returns a render array representation of a variable.
   *
   * @param mixed $input
   *   The variable to export.
   * @param string $name
   *   The label to output before variable.
   * @param string $plugin_id
   *   The plugin ID.
   * @param bool $load_references
   *   If the input is an entity, also load the referenced entities.
   *
   * @return array
   *   String representation of a variable wrapped in a render array.
   */
  public function exportAsRenderable(mixed $input, $name = NULL, $plugin_id = NULL, $load_references = FALSE): array;

}
