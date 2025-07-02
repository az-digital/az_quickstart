<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Masterminds\HTML5;

/**
 * Handles string to DOM and back conversions.
 *
 * Available configuration keys:
 * - method: Action to perform. Possible values:
 *   - import: string to DomDocument.
 *   - export: DomDocument to string.
 * - non_root: (optional) Assume the passed HTML is not a complete hierarchy,
 *   but only a subset inside body element. Defaults to true.
 *
 * The following keys are only used if the method is 'import':
 * - log_messages: (optional) When parsing HTML, libxml may trigger
 *   warnings. If this option is set to true, it will log them as migration
 *   messages. Otherwise, it will not handle it in a special way. Defaults to
 *   true.
 * - version: (optional) The version number of the document as part of the XML
 *   declaration. Defaults to '1.0'.
 * - encoding: (optional) The encoding of the document as part of the XML
 *   declaration. Defaults to 'UTF-8'.
 * - import_method: (optional) What parser to use. Possible values:
 *   - 'html': (default) use dom extension parsing.
 *   - 'html5': use html5 parsing.
 *   - 'xml': use XML parsing.
 *
 * @codingStandardsIgnoreStart
 *
 * Examples:
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: dom
 *       method: import
 *       source: 'body/0/value'
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 * This example above will convert the input string to a DOMDocument object and
 * back, with no explicit processing. It should have few noticeable effects.
 *
 * @code
 * process:
 *   'body/value':
 *     -
 *       plugin: dom
 *       method: import
 *       source: 'body/0/value'
 *       non_root: true
 *       log_messages: true
 *       version: '1.0'
 *       encoding: UTF-8
 *     -
 *       plugin: dom
 *       method: export
 *       non_root: true
 * @endcode
 * This example above will have the same effect as the previous example, since
 * it specifies the default values for all the optional parameters.
 *
 * @codingStandardsIgnoreEnd
 *
 * @MigrateProcessPlugin(
 *   id = "dom"
 * )
 */
class Dom extends ProcessPluginBase {

  /**
   * If parsing warnings should be logged as migrate messages.
   */
  protected bool $logMessages = TRUE;

  /**
   * The HTML contains only the piece inside the body element.
   */
  protected bool $nonRoot = TRUE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!isset($configuration['method'])) {
      throw new \InvalidArgumentException('The "method" must be set.');
    }
    if (!in_array($configuration['method'], ['import', 'export'])) {
      throw new \InvalidArgumentException('The "method" must be "import" or "export".');
    }
    $configuration['import_method'] = $configuration['import_method'] ?? 'html';
    if (!in_array($configuration['import_method'], ['html', 'html5', 'xml'])) {
      throw new \InvalidArgumentException('The "import_method" must be "html", "html5", or "xml".');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration += $this->defaultValues();
    $this->logMessages = (bool) $this->configuration['log_messages'];
    $this->nonRoot = (bool) $this->configuration['non_root'];
  }

  /**
   * Supply default values of all optional parameters.
   *
   *   An array with keys the optional parameters and values the corresponding
   *   defaults.
   */
  protected function defaultValues(): array {
    return [
      'non_root' => TRUE,
      'log_messages' => TRUE,
      'version' => '1.0',
      'encoding' => 'UTF-8',
    ];
  }

  /**
   * Converts a HTML string into a DOMDocument.
   *
   * It is not using \Drupal\Component\Utility\Html::load() because it ignores
   * all errors on import, and therefore incompatible with log_messages
   * option.
   *
   * @param mixed $value
   *   The string to be imported.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process. Normally, just transforming the value
   *   is adequate but very rarely you might need to change two columns at the
   *   same time or something like that.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   *   The document object based on the provided string.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the received $value is not a string.
   */
  public function import($value, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property): \DOMDocument {
    if (!is_string($value)) {
      throw new MigrateException('Cannot import a non-string value.');
    }

    if ($this->logMessages) {
      set_error_handler(static function ($errno, $errstr) use ($migrate_executable): void {
        $migrate_executable->saveMessage($errstr, MigrationInterface::MESSAGE_WARNING);
      });
    }

    if ($this->nonRoot) {
      $html = $this->getNonRootHtml($value);
    }
    else {
      $html = $value;
    }

    $document = new \DOMDocument($this->configuration['version'], $this->configuration['encoding']);
    switch ($this->configuration['import_method']) {
      case 'html5':
        $html5 = new HTML5([
          'target_document' => $document,
          'disable_html_ns' => TRUE,
        ]);
        $html5->loadHTML($html);
        break;

      case 'xml':
        $document->loadXML($html);
        break;

      case 'html':
      default:
        $document->loadHTML($html);
    }

    if ($this->logMessages) {
      restore_error_handler();
    }

    return $document;
  }

  /**
   * Converts a DOMDocument into a HTML string.
   *
   * @param mixed $value
   *   The document to be exported.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process. Normally, just transforming the value
   *   is adequate but very rarely you might need to change two columns at the
   *   same time or something like that.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return string
   *   The HTML string corresponding to the provided document object.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the received $value is not a \DOMDocument.
   */
  public function export($value, MigrateExecutableInterface $migrate_executable, Row $row, string $destination_property) {
    if (!$value instanceof \DOMDocument) {
      $value_description = (gettype($value) == 'object') ? get_class($value) : gettype($value);
      throw new MigrateException(sprintf('Cannot export a "%s".', $value_description));
    }

    if ($this->nonRoot) {
      return Html::serialize($value);
    }
    return $value->saveHTML();
  }

  /**
   * Builds an full html string based on a partial.
   *
   * @param string $partial
   *   A subset of a full html string. For instance the contents of the body
   *   element.
   */
  protected function getNonRootHtml(string $partial): string {
    $replacements = [
      "\n" => '',
      '!encoding' => strtolower($this->configuration['encoding']),
      '!value' => $partial,
    ];
    // Prepend the html with a header using the configured source encoding.
    // By default, loadHTML() assumes ISO-8859-1.
    $html_template = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=!encoding" /></head>
<body>!value</body>
</html>
EOD;
    return strtr($html_template, $replacements);
  }

}
