<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Obtain XML data for migration using the XMLReader pull parser.
 *
 * XMLReader reader performs incremental parsing of an XML file. This allows
 * parsing very large XML sources (e.g. 200MB WordPress dumps), which reduces
 * the memory usage and increases the performance. The disadvantage is that it's
 * not possible to use XPath search across the entire source.
 *
 * @DataParser(
 *   id = "xml",
 *   title = @Translation("XML")
 * )
 */
class Xml extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  use XmlTrait;

  /**
   * The XMLReader we are encapsulating.
   */
  protected \XMLReader $reader;

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Array of the element names from the query.
   *
   * 0-based from the first (root) element. For example, '//file/article' would
   * be stored as [0 => 'file', 1 => 'article'].
   */
  protected array $elementsToMatch = [];

  /**
   * An optional xpath predicate.
   *
   * Restricts the matching elements based on values in their children. Parsed
   * from the element query at construct time.
   */
  protected ?string $xpathPredicate = NULL;

  /**
   * Array representing the path to the current element as we traverse the XML.
   *
   * For example, if in an XML string like '<file><article>...</article></file>'
   * we are positioned within the article element, currentPath will be
   * [0 => 'file', 1 => 'article'].
   */
  protected array $currentPath = [];

  /**
   * Retains all elements with a given name to support extraction from parents.
   *
   * This is a hack to support field extraction of values in parents
   * of the 'context node' - ie, if $this->fields() has something like '..\nid'.
   * Since we are using a streaming xml processor, it is too late to snoop
   * around parent elements again once we've located an element of interest. So,
   * grab elements with matching names and their depths, and refer back to it
   * when building the source row.
   */
  protected array $parentXpathCache = [];

  /**
   * Hash of the element names that should be captured into $parentXpathCache.
   */
  protected array $parentElementsOfInterest = [];

  /**
   * Element name matching mode.
   *
   * When matching element names, whether to compare to the namespace-prefixed
   * name, or the local name.
   */
  protected bool $prefixedName = FALSE;

  /**
   * Constructs a new XML data parser.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->fileSystem = $file_system;

    $this->reader = new \XMLReader();

    // Suppress errors during parsing, so we can pick them up after.
    libxml_use_internal_errors(TRUE);

    // Parse the element query. First capture group is the element path, second
    // (if present) is the attribute.
    preg_match_all('|^/([^\[]+)\[?(.*?)]?$|', $configuration['item_selector'], $matches);
    $element_path = $matches[1][0];
    $this->elementsToMatch = explode('/', $element_path);
    $predicate = $matches[2][0];
    if ($predicate) {
      $this->xpathPredicate = $predicate;
    }

    // If the element path contains any colons, it must be specifying
    // namespaces, so we need to compare using the prefixed element
    // name in next().
    if (strpos($element_path, ':')) {
      $this->prefixedName = TRUE;
    }

    foreach ($this->fieldSelectors() as $field_name => $xpath) {
      $prefix = substr($xpath, 0, 3);
      if ($prefix === '../') {
        $this->parentElementsOfInterest[] = str_replace('../', '', $xpath);
      }
      elseif ($prefix === '..\\') {
        $this->parentElementsOfInterest[] = str_replace('..\\', '', $xpath);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DataParserPluginBase {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_system')
    );
  }

  /**
   * Builds a \SimpleXmlElement rooted at the iterator's current location.
   *
   * The resulting SimpleXmlElement also contains any child nodes of the current
   * element.
   *
   * @return \SimpleXmlElement|null
   *   A \SimpleXmlElement when the document is parseable, or null if a
   *   parsing error occurred.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getSimpleXml(): ?\SimpleXMLElement {
    $node = $this->reader->expand();
    if ($node) {
      // We must associate the DOMNode with a DOMDocument to be able to import
      // it into SimpleXML. Despite appearances, this is almost twice as fast as
      // simplexml_load_string($this->readOuterXML());
      $dom = new \DOMDocument();
      $node = $dom->importNode($node, TRUE);
      $dom->appendChild($node);
      $sxml_elem = simplexml_import_dom($node);
      $this->registerNamespaces($sxml_elem);
      return $sxml_elem;
    }
    else {
      foreach (libxml_get_errors() as $error) {
        $error_string = self::parseLibXmlError($error);
        throw new MigrateException($error_string);
      }
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    // Reset our path tracker.
    $this->currentPath = [];
    parent::rewind();
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url): bool {
    // (Re)open the provided URL.
    $this->reader->close();

    // Fetch the data and save it to a temporary file.
    $xml_data = $this->getDataFetcherPlugin()->getResponseContent($url);
    $url = $this->fileSystem->tempnam('temporary://', 'file');
    if (file_put_contents($url, $xml_data) === FALSE) {
      throw new MigrateException('Unable to save temporary XML');
    }

    // Clear XML error buffer. Other Drupal code that executed during the
    // migration may have polluted the error buffer and could create false
    // positives in our error check below. We are only concerned with errors
    // that occur from attempting to load the XML string into an object here.
    libxml_clear_errors();

    return $this->reader->open($url, NULL, \LIBXML_NOWARNING);
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $target_element = NULL;

    // Loop over each node in the XML file, looking for elements at a path
    // matching the input query string (represented in $this->elementsToMatch).
    while ($this->reader->read()) {
      if ($this->reader->nodeType == \XMLReader::ELEMENT) {
        if ($this->prefixedName) {
          $this->currentPath[$this->reader->depth] = $this->reader->name;
          if (in_array($this->reader->name, $this->parentElementsOfInterest)) {
            $this->parentXpathCache[$this->reader->depth][$this->reader->name][] = $this->getSimpleXml();
          }
        }
        else {
          $this->currentPath[$this->reader->depth] = $this->reader->localName;
          if (in_array($this->reader->localName, $this->parentElementsOfInterest)) {
            $this->parentXpathCache[$this->reader->depth][$this->reader->name][] = $this->getSimpleXml();
          }
        }
        if ($this->currentPath == $this->elementsToMatch) {
          // We're positioned to the right element path - build the SimpleXML
          // object to enable proper xpath predicate evaluation.
          $target_element = $this->getSimpleXml();
          if ($target_element !== NULL) {
            if (empty($this->xpathPredicate) || $this->predicateMatches($target_element)) {
              break;
            } else {
              // Set target back to Null since this didn't match a predicate
              $target_element = NULL;
            }
          }
        }
      }
      elseif ($this->reader->nodeType == \XMLReader::END_ELEMENT) {
        // Remove this element and any deeper ones from the current path.
        foreach ($this->currentPath as $depth => $name) {
          if ($depth >= $this->reader->depth) {
            unset($this->currentPath[$depth]);
          }
        }
        foreach ($this->parentXpathCache as $depth => $elements) {
          if ($depth > $this->reader->depth) {
            unset($this->parentXpathCache[$depth]);
          }
        }
      }
    }

    // If we've found the desired element, populate the currentItem and
    // currentId with its data.
    if ($target_element !== FALSE && !is_null($target_element)) {
      $this->registerNamespaces($target_element);
      foreach ($this->fieldSelectors() as $field_name => $xpath) {
        $prefix = substr($xpath, 0, 3);
        if (in_array($prefix, ['../', '..\\'])) {
          $name = str_replace($prefix, '', $xpath);
          $up = substr_count($xpath, $prefix);
          $values = $this->getAncestorElements($up, $name);
        }
        else {
          $values = $target_element->xpath($xpath);
        }
        foreach ($values as $value) {
          // If the SimpleXMLElement doesn't render to a string of any sort,
          // and has children then return the whole object for the process
          // plugin or other row manipulation.
          if ($value->children() && !trim((string) $value)) {
            $this->currentItem[$field_name][] = $value;
          }
          else {
            $this->currentItem[$field_name][] = (string) $value;
          }
        }
      }
      // Reduce single-value arrays to scalars.
      foreach ($this->currentItem as $field_name => $values) {
        // We cannot use reset for SimpleXmlElement because it might have
        // attributes that are not counted. Get the first value, even if there
        // are more values available.
        if (is_array($values) && count($values) == 1) {
          $this->currentItem[$field_name] = reset($values);
        }
      }
    }
  }

  /**
   * Tests whether the iterator's xpath predicate matches the provided element.
   *
   * Has some limitations esp. in that it is easy to write predicates that
   * reference things outside this SimpleXmlElement's tree, but "simpler"
   * predicates should work as expected.
   *
   * @param \SimpleXMLElement $elem
   *   The element to test.
   *
   *   True if the element matches the predicate, false if not.
   */
  protected function predicateMatches(\SimpleXMLElement $elem): bool {
    return !empty($elem->xpath('/*[' . $this->xpathPredicate . ']'));
  }

  /**
   * Gets an ancestor SimpleXMLElement, if the element name was registered.
   *
   * Gets the SimpleXMLElement some number of levels above the iterator
   * having the given name, but only for element names that this
   * Xml data parser was told to retain for future reference through the
   * constructor's $parent_elements_of_interest.
   *
   * @param int $levels_up
   *   The number of levels back towards the root of the DOM tree to ascend
   *   before searching for the named element.
   * @param string $name
   *   The name of the desired element.
   *
   * @return \SimpleXMLElement|false
   *   The element matching the level and name requirements, or false if it is
   *   not present or was not retained.
   */
  public function getAncestorElements($levels_up, $name) {
    if ($levels_up > 0) {
      $levels_up *= -1;
    }
    $ancestor_depth = $this->reader->depth + $levels_up + 1;
    if ($ancestor_depth < 0) {
      return FALSE;
    }

    if (array_key_exists($ancestor_depth, $this->parentXpathCache) && array_key_exists($name, $this->parentXpathCache[$ancestor_depth])) {
      return $this->parentXpathCache[$ancestor_depth][$name];
    }
    else {
      return FALSE;
    }
  }

}
