<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Uses either str_replace() or preg_replace() function on a source string.
 *
 * Available configuration keys:
 * - search: The value or pattern being searched for. It can be either a string
 *   or an array with strings.
 * - replace: The replacement value that replaces found search values. It can be
 *   either a string or an array with strings.
 * - regex: (optional) If not empty, then preg_replace() function will be used
 *   instead of str_replace(). Defaults to FALSE.
 * - case_insensitive: (optional) If not empty, then str_ireplace() function
 *   will be used instead of str_replace(). Defaults to FALSE. Ignored if
 *   'regex' is enabled.
 *
 * Depending on the value of 'regex', the rules for
 * @link http://php.net/manual/function.str-replace.php str_replace @endlink
 * or
 * @link http://php.net/manual/function.preg-replace.php preg_replace @endlink
 * apply. This means that you can provide arrays as values, your replace string
 * can include backreferences, etc.
 *
 * To do a simple hardcoded string replace, use the following:
 * @code
 * field_text:
 *   plugin: str_replace
 *   source: text
 *   search: et
 *   replace: that
 * @endcode
 * If the value of text is "vero eos et accusam et justo vero" in source,
 * field_text will be "vero eos that accusam that justo vero".
 *
 * Case-insensitive searches can be achieved using the following:
 * @code
 * field_text:
 *   plugin: str_replace
 *   case_insensitive: true
 *   source: text
 *   search: vero
 *   replace: that
 * @endcode
 * If the value of text is "VERO eos et accusam et justo vero" in source,
 * field_text will be "that eos et accusam et justo that".
 *
 * Also, regular expressions can be matched using:
 * @code
 * field_text:
 *   plugin: str_replace
 *   regex: true
 *   source: text
 *   search: /[0-9]{3}/
 *   replace: the
 * @endcode
 * If the value of text is "vero eos et 123 accusam et justo 123 duo" in source,
 * field_text will be "vero eos et the accusam et justo the duo".
 *
 * Multiple values can be matched like this:
 * @code
 * field_text:
 *   plugin: str_replace
 *   source: text
 *   search: ["AT", "CH", "DK"]
 *   replace: ["Austria", "Switzerland", "Denmark"]
 * @endcode
 *
 * Replace with a regex backreference like this:
 * @code
 * field_text:
 *   plugin: str_replace
 *   regex: true
 *   source: text
 *   search: /@(\S+)/
 *   replace: $1
 * @endcode
 * If the value of text is "@username" in source, field_text will be "username".
 *
 * @MigrateProcessPlugin(
 *   id = "str_replace"
 * )
 */
class StrReplace extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   */
  protected ?bool $multiple = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['search'])) {
      throw new \InvalidArgumentException('The "search" must be set.');
    }
    if (!isset($configuration['replace'])) {
      throw new \InvalidArgumentException('The "replace" must be set.');
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->multiple = is_array($value);
    $this->configuration += [
      'case_insensitive' => FALSE,
      'regex' => FALSE,
    ];
    $function = 'str_replace';
    if ($this->configuration['case_insensitive']) {
      $function = 'str_ireplace';
    }
    if ($this->configuration['regex']) {
      $function = 'preg_replace';
    }
    if($this->multiple) {
      foreach($value as $key => $item) {
        $item = (string) $item;
        $value[$key] = $function($this->configuration['search'], $this->configuration['replace'], $item);
      }
      return $value;
    }
    $value = (string) $value;
    return $function($this->configuration['search'], $this->configuration['replace'], $value);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple(): bool {
    return $this->multiple;
  }

}
