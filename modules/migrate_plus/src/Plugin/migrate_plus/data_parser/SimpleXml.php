<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain XML data for migration using the SimpleXML API.
 *
 * SimpleXML parses the whole file into memory, which allows using XPath
 * expression in the item selector. For large XML sources it results in
 * consuming lots of memory, which can be undesirable. If you run into memory
 * issues, then consider using the 'xml' data parser.
 *
 * @DataParser(
 *   id = "simple_xml",
 *   title = @Translation("Simple XML")
 * )
 */
class SimpleXml extends DataParserPluginBase {

  use XmlTrait;

  /**
   * Array of matches from item_selector.
   *
   * @var \SimpleXMLElement[]|bool
   */
  protected $matches = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Suppress errors during parsing, so we can pick them up after.
    libxml_use_internal_errors(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url): bool {
    // Clear XML error buffer. Other Drupal code that executed during the
    // migration may have polluted the error buffer and could create false
    // positives in our error check below. We are only concerned with errors
    // that occur from attempting to load the XML string into an object here.
    libxml_clear_errors();

    $xml_data = $this->getDataFetcherPlugin()->getResponseContent($url);
    $xml = simplexml_load_string(trim($xml_data));
    foreach (libxml_get_errors() as $error) {
      $error_string = self::parseLibXmlError($error);
      throw new MigrateException($error_string);
    }
    $this->registerNamespaces($xml);
    $xpath = $this->configuration['item_selector'];
    $this->matches = $xml->xpath($xpath);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $target_element = array_shift($this->matches);

    // If we've found the desired element, populate the currentItem and
    // currentId with its data.
    if ($target_element !== FALSE && !is_null($target_element)) {
      $this->registerNamespaces($target_element);
      foreach ($this->fieldSelectors() as $field_name => $xpath) {
        foreach ($target_element->xpath($xpath) as $value) {
          if ($value->children() && !trim((string) $value)) {
            $this->currentItem[$field_name][] = $value;
          }
          else {
            $this->currentItem[$field_name][] = (string) $value;
          }
        }
      }
      // Reduce single-value results to scalars.
      foreach ($this->currentItem as $field_name => $values) {
        if (is_array($values) && count($values) == 1) {
          $this->currentItem[$field_name] = reset($values);
        }
      }
    }
  }

}
