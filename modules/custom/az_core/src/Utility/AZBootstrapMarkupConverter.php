<?php

namespace Drupal\az_core\Utility;

use Masterminds\HTML5;

/**
 * Converts Arizona Bootstrap 2 markup to Arizona Bootstrap 5 markup.
 */
final class AZBootstrapMarkupConverter {

  /**
   * Uses DOM parser to parse HTML without modifying it.
   *
   * Used for comparing original content after parsing but before conversion.
   *
   * @param string $text
   *   Text containing HTML markup.
   *
   * @return string|bool
   *   Parsed text value or FALSE if DOM parsing was unsuccessful.
   */
  public static function parse($text) {
    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    // Create a base document.
    $dom = $html5->parse('<!DOCTYPE html><html><body></body></html>');
    $body = $dom->getElementsByTagName('body')->item(0);

    // Parse the fragment.
    $fragment = $html5->parseFragment($text);

    if (!$fragment) {
      return FALSE;
    }

    // Import the fragment into our document.
    foreach ($fragment->childNodes as $child) {
      $imported = $dom->importNode($child, TRUE);
      $body->appendChild($imported);
    }

    // Return parsed but unconverted content.
    $result = '';
    foreach ($body->childNodes as $child) {
      $result .= $html5->saveHTML($child);
    }
    return $result;
  }

  /**
   * Method to be used as callable for AZContentFieldUpdater service.
   *
   * Compares the parsed original content with converted content.
   *
   * @param string $text
   *   Text containing HTML markup.
   *
   * @return string
   *   Returns the converted value if changes were made, otherwise original
   *   value.
   */
  public static function compareProcessor($text) {
    // First just parse the content.
    $parsed = static::parse($text);
    if ($parsed === FALSE) {
      return $text;
    }

    // Then convert it.
    $converted = static::convert($text);
    if ($converted === FALSE) {
      return $text;
    }

    // Only return converted value if it's different from parsed.
    if ($parsed !== $converted) {
      return $converted;
    }

    return $text;
  }

  /**
   * Uses DOM parser to remap old AZ Bootstrap attribute values to new values.
   *
   * @param string $text
   *   Text containing HTML markup.
   *
   * @return string|bool
   *   Converted text value or FALSE if DOM parsing was unsuccessful.
   */
  public static function convert($text) {
    $html5 = new HTML5(['disable_html_ns' => TRUE]);
    // Create a base document.
    $dom = $html5->parse('<!DOCTYPE html><html><body></body></html>');
    $body = $dom->getElementsByTagName('body')->item(0);

    // Parse the fragment.
    $fragment = $html5->parseFragment($text);

    if (!$fragment) {
      return FALSE;
    }

    // Import the fragment into our document.
    foreach ($fragment->childNodes as $child) {
      $imported = $dom->importNode($child, TRUE);
      $body->appendChild($imported);
    }

    $xpath = new \DOMXPath($dom);

    // Convert legacy Bootstrap data-* attributes to data-bs-*.
    foreach ($dom->getElementsByTagName('*') as $element) {
      if ($element->attributes !== NULL) {
        foreach (iterator_to_array($element->attributes) as $attr) {
          $regex = '/^data-(' . implode('|', array_map('preg_quote', static::LEGACY_DATA_ATTRIBUTES)) . ')$/';
          if (preg_match($regex, $attr->name, $matches)) {
            $new_name = 'data-bs-' . $matches[1];
            $element->setAttribute($new_name, $attr->value);
            $element->removeAttribute($attr->name);
          }
        }
      }
    }

    foreach ($xpath->query('//*[@class]') as $node) {
      if ($node instanceof \DOMElement) {
        $classes = explode(' ', $node->getAttribute('class'));
        $updated = FALSE;

        foreach ($classes as &$class) {
          if (isset(static::CLASS_MAP[$class])) {
            $class = static::CLASS_MAP[$class];
            $updated = TRUE;
          }
        }

        if ($updated) {
          $node->setAttribute('class', implode(' ', $classes));
        }
      }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $result = '';
    foreach ($body->childNodes as $child) {
      $result .= $html5->saveHTML($child);
    }
    return $result;
  }

  /**
   * List of Bootstrap data-* attributes that should become data-bs-*.
   */
  const LEGACY_DATA_ATTRIBUTES = [
    'animation',
    'autohide',
    'backdrop',
    'boundary',
    'container',
    'content',
    'delay',
    'dismiss',
    'html',
    'interval',
    'keyboard',
    'method',
    'offset',
    'pause',
    'placement',
    'reference',
    'ride',
    'slide',
    'target',
    'template',
    'toggle',
    'touch',
    'trigger',
    'wrap',
  ];

  /**
   * Map AZ Bootstrap 2 classes to AZ Bootstrap 5 classes.
   */
  const CLASS_MAP = [
    'badge-primary' => 'text-bg-primary',
    'badge-secondary' => 'text-bg-secondary',
    'form-group' => 'mb-3',
    'form-inline' => 'd-flex align-items-center',
    'custom-select' => 'form-select',
    'sr-only' => 'visually-hidden',
    'text-left' => 'text-start',
    'text-right' => 'text-end',
    'btn-block' => 'w-100',
    'ml-auto' => 'ms-auto',
    'mr-auto' => 'me-auto',
    'bold' => 'fw-bold',
    'btn-hollow-reverse' => 'btn-outline-white',
    'col-12' => 'col-12 position-relative',
    // Remove btn class when it conflicts with nav-link.
    // @todo We probably need to handle this situation differently to avoid
    // breaking legitimate usage of btn class (e.g. in combination with other
    // btn-* classes).
    /* 'btn' => '',*/
    // Margin class mappings for Bootstrap 5.
    'ml-0' => 'ms-0',
    'ml-1' => 'ms-1',
    'ml-2' => 'ms-2',
    'ml-3' => 'ms-3',
    'ml-4' => 'ms-4',
    'ml-5' => 'ms-5',
    'mr-0' => 'me-0',
    'mr-1' => 'me-1',
    'mr-2' => 'me-2',
    'mr-3' => 'me-3',
    'mr-4' => 'me-4',
    'mr-5' => 'me-5',
    'mr-sm-0' => 'me-sm-0',
    'mr-sm-1' => 'me-sm-1',
    'mr-sm-2' => 'me-sm-2',
    'mr-sm-3' => 'me-sm-3',
    'mr-sm-4' => 'me-sm-4',
    'mr-sm-5' => 'me-sm-5',
    'ml-sm-0' => 'ms-sm-0',
    'ml-sm-1' => 'ms-sm-1',
    'ml-sm-2' => 'ms-sm-2',
    'ml-sm-3' => 'ms-sm-3',
    'ml-sm-4' => 'ms-sm-4',
    'ml-sm-5' => 'ms-sm-5',
    'mr-md-0' => 'me-md-0',
    'mr-md-1' => 'me-md-1',
    'mr-md-2' => 'me-md-2',
    'mr-md-3' => 'me-md-3',
    'mr-md-4' => 'me-md-4',
    'mr-md-5' => 'me-md-5',
    'ml-md-0' => 'ms-md-0',
    'ml-md-1' => 'ms-md-1',
    'ml-md-2' => 'ms-md-2',
    'ml-md-3' => 'ms-md-3',
    'ml-md-4' => 'ms-md-4',
    'ml-md-5' => 'ms-md-5',
    'mr-lg-0' => 'me-lg-0',
    'mr-lg-1' => 'me-lg-1',
    'mr-lg-2' => 'me-lg-2',
    'mr-lg-3' => 'me-lg-3',
    'mr-lg-4' => 'me-lg-4',
    'mr-lg-5' => 'me-lg-5',
    'ml-lg-0' => 'ms-lg-0',
    'ml-lg-1' => 'ms-lg-1',
    'ml-lg-2' => 'ms-lg-2',
    'ml-lg-3' => 'ms-lg-3',
    'ml-lg-4' => 'ms-lg-4',
    'ml-lg-5' => 'ms-lg-5',
    'mr-xl-0' => 'me-xl-0',
    'mr-xl-1' => 'me-xl-1',
    'mr-xl-2' => 'me-xl-2',
    'mr-xl-3' => 'me-xl-3',
    'mr-xl-4' => 'me-xl-4',
    'mr-xl-5' => 'me-xl-5',
    'ml-xl-0' => 'ms-xl-0',
    'ml-xl-1' => 'ms-xl-1',
    'ml-xl-2' => 'ms-xl-2',
    'ml-xl-3' => 'ms-xl-3',
    'ml-xl-4' => 'ms-xl-4',
    'ml-xl-5' => 'ms-xl-5',
    // Padding class mappings for Bootstrap 5.
    'pl-0' => 'ps-0',
    'pl-1' => 'ps-1',
    'pl-2' => 'ps-2',
    'pl-3' => 'ps-3',
    'pl-4' => 'ps-4',
    'pl-5' => 'ps-5',
    'pr-0' => 'pe-0',
    'pr-1' => 'pe-1',
    'pr-2' => 'pe-2',
    'pr-3' => 'pe-3',
    'pr-4' => 'pe-4',
    'pl-lg-0' => 'ps-lg-0',
    'pr-lg-1' => 'pe-lg-1',
    'col-lg-offset-2' => 'offset-lg-2',
    'thead-dark' => 'table-dark',
    'thead-light' => 'table-light',
    // Background color mappings from az-digital/az_quickstart#4323.
    'bg-transparent-white' => 'text-bg-transparent-white',
    'bg-transparent-black' => 'text-bg-transparent-black',
    'bg-red' => 'text-bg-red',
    'bg-blue' => 'text-bg-blue',
    'bg-sky' => 'text-bg-sky',
    'bg-oasis' => 'text-bg-oasis',
    'bg-azurite' => 'text-bg-azurite',
    'bg-midnight' => 'text-bg-midnight',
    'bg-bloom' => 'text-bg-bloom',
    'bg-chili' => 'text-bg-chili',
    'bg-cool-gray' => 'text-bg-cool-gray',
    'bg-warm-gray' => 'text-bg-warm-gray',
    'bg-leaf' => 'text-bg-leaf',
    'bg-river' => 'text-bg-river',
    'bg-silver' => 'text-bg-silver',
    'bg-ash' => 'text-bg-ash',
    'bg-sage' => 'text-bg-sage',
    'bg-smoke' => 'text-bg-smoke',
    // Add more as needed...
  ];

}
