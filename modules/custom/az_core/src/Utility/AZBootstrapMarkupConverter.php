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
    /***************************************************************************
     * Screen reader / visually hidden classes.
     */
    'sr-only' => 'visually-hidden',
    'sr-only-focusable' => 'visually-hidden-focusable',
    /***************************************************************************
     * Float classes.
     */
    'float-left' => 'float-start',
    'float-right' => 'float-end',
    'float-sm-left' => 'float-start',
    'float-sm-right' => 'float-end',
    'float-md-left' => 'float-start',
    'float-md-right' => 'float-end',
    'float-lg-left' => 'float-start',
    'float-lg-right' => 'float-end',
    'float-xl-left' => 'float-start',
    'float-xl-right' => 'float-end',
    /***************************************************************************
     * Form classes.
     */
    'form-group' => 'mb-3',
    'form-inline' => 'd-flex align-items-center',
    'form-row' => 'd-flex flex-wrap gap-2',
    'custom-select' => 'form-select',
    /***************************************************************************
     * Table classes.
     */
    'thead-dark' => 'table-dark',
    'thead-light' => 'table-light',
    /***************************************************************************
     * Font / text style classes.
     */
    'font-weight-normal' => 'fw-normal',
    'bold' => 'fw-bold',
    'font-weight-bold' => 'fw-bold',
    'font-italic' => 'fst-italic',
    'text-monospace' => 'font-monospace',
    'text-left' => 'text-start',
    'text-sm-left' => 'text-sm-start',
    'text-md-left' => 'text-md-start',
    'text-lg-left' => 'text-lg-start',
    'text-xl-left' => 'text-xl-start',
    'text-right' => 'text-end',
    'text-sm-right' => 'text-sm-end',
    'text-md-right' => 'text-md-end',
    'text-lg-right' => 'text-lg-end',
    'text-xl-right' => 'text-xl-end',
    'text-muted' => 'text-body-secondary',
    'ul-triangles' => 'az-list-triangles',
    /***************************************************************************
     * Button classes.
     */
    'btn-block' => 'w-100',
    'btn-hollow' => 'btn-outline',
    'btn-hollow-reverse' => 'btn-outline-white',
    // Remove btn class when it conflicts with nav-link.
    // @todo We probably need to handle this situation differently to avoid
    // breaking legitimate usage of btn class (e.g. in combination with other
    // btn-* classes).
    /* 'btn' => '',*/
    'close' => 'btn-close',
    /***************************************************************************
     * Dropdown classes.
     */
    'dropleft' => 'dropstart',
    'dropright' => 'dropend',
    'dropdown-menu-left' => 'dropdown-menu-start',
    'dropdown-menu-sm-left' => 'dropdown-menu-sm-start',
    'dropdown-menu-md-left' => 'dropdown-menu-md-start',
    'dropdown-menu-lg-left' => 'dropdown-menu-lg-start',
    'dropdown-menu-xl-left' => 'dropdown-menu-xl-start',
    'dropdown-menu-right' => 'dropdown-menu-end',
    'dropdown-menu-sm-right' => 'dropdown-menu-sm-end',
    'dropdown-menu-md-right' => 'dropdown-menu-md-end',
    'dropdown-menu-lg-right' => 'dropdown-menu-lg-end',
    'dropdown-menu-xl-right' => 'dropdown-menu-xl-end',
    /***************************************************************************
     * Border classes.
     */
    'border-left' => 'border-start',
    'border-left-0' => 'border-start-0',
    'border-right' => 'border-end',
    'border-right-0' => 'border-end-0',
    'card-borderless' => 'border-0',
    'rounded-left' => 'rounded-start',
    'rounded-right' => 'rounded-end',
    'rounded-sm' => 'rounded-1',
    'rounded-lg' => 'rounded-2',
    /***************************************************************************
     * Badge classes.
     */
    'badge-primary' => 'text-bg-primary',
    'badge-secondary' => 'text-bg-secondary',
    'badge-blue' => 'text-bg-blue',
    'badge-red' => 'text-bg-red',
    'badge-success' => 'text-bg-success',
    'badge-danger' => 'text-bg-danger',
    'badge-warning' => 'text-bg-warning',
    'badge-info' => 'text-bg-info',
    'badge-light' => 'text-bg-light',
    'badge-dark' => 'text-bg-dark',
    'badge-pill' => 'rounded-pill',
    /***************************************************************************
     * Background color mappings from az-digital/az_quickstart#4323.
     */
    'bg-transparent-white' => 'text-bg-transparent-white',
    'bg-transparent-black' => 'text-bg-transparent-black',
    'bg-red' => 'text-bg-red',
    'bg-bloom' => 'text-bg-bloom',
    'bg-chili' => 'text-bg-chili',
    'bg-blue' => 'text-bg-blue',
    'bg-sky' => 'text-bg-sky',
    'bg-oasis' => 'text-bg-oasis',
    'bg-azurite' => 'text-bg-azurite',
    'bg-midnight' => 'text-bg-midnight',
    'bg-cool-gray' => 'text-bg-cool-gray',
    'bg-warm-gray' => 'text-bg-warm-gray',
    'bg-leaf' => 'text-bg-leaf',
    'bg-river' => 'text-bg-river',
    'bg-silver' => 'text-bg-silver',
    'bg-mesa' => 'text-bg-mesa',
    'bg-ash' => 'text-bg-ash',
    'bg-sage' => 'text-bg-sage',
    'bg-white' => 'text-bg-white',
    'bg-black' => 'text-bg-black',
    'bg-primary' => 'text-bg-primary',
    'bg-secondary' => 'text-bg-secondary',
    'bg-success' => 'text-bg-success',
    'bg-danger' => 'text-bg-danger',
    'bg-warning' => 'text-bg-warning',
    'bg-info' => 'text-bg-info',
    'bg-light' => 'text-bg-light',
    'bg-dark' => 'text-bg-dark',
    'bg-gray-100' => 'text-bg-gray-100',
    'bg-gray-200' => 'text-bg-gray-200',
    'bg-gray-300' => 'text-bg-gray-300',
    'bg-gray-400' => 'text-bg-gray-400',
    'bg-gray-500' => 'text-bg-gray-500',
    'bg-gray-600' => 'text-bg-gray-600',
    'bg-gray-700' => 'text-bg-gray-700',
    'bg-gray-800' => 'text-bg-gray-800',
    'bg-gray-900' => 'text-bg-gray-900',
    /***************************************************************************
     * Grid layout utility classes.
     */
    'col-12' => 'col-12 position-relative',
    'no-gutters' => 'g-0',
    // Column offset classes.
    'col-sm-offset-0' => 'offset-sm-0',
    'col-sm-offset-1' => 'offset-sm-1',
    'col-sm-offset-2' => 'offset-sm-2',
    'col-sm-offset-3' => 'offset-sm-3',
    'col-sm-offset-4' => 'offset-sm-4',
    'col-sm-offset-5' => 'offset-sm-5',
    'col-sm-offset-6' => 'offset-sm-6',
    'col-sm-offset-7' => 'offset-sm-7',
    'col-sm-offset-8' => 'offset-sm-8',
    'col-sm-offset-9' => 'offset-sm-9',
    'col-sm-offset-10' => 'offset-sm-10',
    'col-sm-offset-20' => 'offset-sm-20',
    'col-sm-offset-30' => 'offset-sm-30',
    'col-md-offset-0' => 'offset-md-0',
    'col-md-offset-1' => 'offset-md-1',
    'col-md-offset-2' => 'offset-md-2',
    'col-md-offset-3' => 'offset-md-3',
    'col-md-offset-4' => 'offset-md-4',
    'col-md-offset-5' => 'offset-md-5',
    'col-md-offset-6' => 'offset-md-6',
    'col-md-offset-7' => 'offset-md-7',
    'col-md-offset-8' => 'offset-md-8',
    'col-md-offset-9' => 'offset-md-9',
    'col-md-offset-10' => 'offset-md-10',
    'col-md-offset-20' => 'offset-md-20',
    'col-md-offset-30' => 'offset-md-30',
    'col-lg-offset-0' => 'offset-lg-0',
    'col-lg-offset-1' => 'offset-lg-1',
    'col-lg-offset-2' => 'offset-lg-2',
    'col-lg-offset-3' => 'offset-lg-3',
    'col-lg-offset-4' => 'offset-lg-4',
    'col-lg-offset-5' => 'offset-lg-5',
    'col-lg-offset-6' => 'offset-lg-6',
    'col-lg-offset-7' => 'offset-lg-7',
    'col-lg-offset-8' => 'offset-lg-8',
    'col-lg-offset-9' => 'offset-lg-9',
    'col-lg-offset-10' => 'offset-lg-10',
    'col-lg-offset-20' => 'offset-lg-20',
    'col-lg-offset-30' => 'offset-lg-30',
    'col-xl-offset-0' => 'offset-xl-0',
    'col-xl-offset-1' => 'offset-xl-1',
    'col-xl-offset-2' => 'offset-xl-2',
    'col-xl-offset-3' => 'offset-xl-3',
    'col-xl-offset-4' => 'offset-xl-4',
    'col-xl-offset-5' => 'offset-xl-5',
    'col-xl-offset-6' => 'offset-xl-6',
    'col-xl-offset-7' => 'offset-xl-7',
    'col-xl-offset-8' => 'offset-xl-8',
    'col-xl-offset-9' => 'offset-xl-9',
    'col-xl-offset-10' => 'offset-xl-10',
    'col-xl-offset-20' => 'offset-xl-20',
    'col-xl-offset-30' => 'offset-xl-30',
    /***************************************************************************
     * Spacing utility classes.
     */
    // Margin class mappings.
    'ml-0' => 'ms-0',
    'ml-1' => 'ms-1',
    'ml-2' => 'ms-2',
    'ml-3' => 'ms-3',
    'ml-4' => 'ms-4',
    'ml-5' => 'ms-5',
    'ml-6' => 'ms-6',
    'ml-7' => 'ms-7',
    'ml-8' => 'ms-8',
    'ml-9' => 'ms-9',
    'ml-10' => 'ms-10',
    'ml-20' => 'ms-20',
    'ml-30' => 'ms-30',
    'ml-auto' => 'ms-auto',
    'mr-0' => 'me-0',
    'mr-1' => 'me-1',
    'mr-2' => 'me-2',
    'mr-3' => 'me-3',
    'mr-4' => 'me-4',
    'mr-5' => 'me-5',
    'mr-6' => 'me-6',
    'mr-7' => 'me-7',
    'mr-8' => 'me-8',
    'mr-9' => 'me-9',
    'mr-10' => 'me-10',
    'mr-20' => 'me-20',
    'mr-30' => 'me-30',
    'mr-auto' => 'me-auto',
    'ml-sm-0' => 'ms-sm-0',
    'ml-sm-1' => 'ms-sm-1',
    'ml-sm-2' => 'ms-sm-2',
    'ml-sm-3' => 'ms-sm-3',
    'ml-sm-4' => 'ms-sm-4',
    'ml-sm-5' => 'ms-sm-5',
    'ml-sm-6' => 'ms-sm-6',
    'ml-sm-7' => 'ms-sm-7',
    'ml-sm-8' => 'ms-sm-8',
    'ml-sm-9' => 'ms-sm-9',
    'ml-sm-10' => 'ms-sm-10',
    'ml-sm-20' => 'ms-sm-20',
    'ml-sm-30' => 'ms-sm-30',
    'ml-sm-auto' => 'ms-sm-auto',
    'mr-sm-0' => 'me-sm-0',
    'mr-sm-1' => 'me-sm-1',
    'mr-sm-2' => 'me-sm-2',
    'mr-sm-3' => 'me-sm-3',
    'mr-sm-4' => 'me-sm-4',
    'mr-sm-5' => 'me-sm-5',
    'mr-sm-6' => 'me-sm-6',
    'mr-sm-7' => 'me-sm-7',
    'mr-sm-8' => 'me-sm-8',
    'mr-sm-9' => 'me-sm-9',
    'mr-sm-10' => 'me-sm-10',
    'mr-sm-20' => 'me-sm-20',
    'mr-sm-30' => 'me-sm-30',
    'mr-sm-auto' => 'me-sm-auto',
    'ml-md-0' => 'ms-md-0',
    'ml-md-1' => 'ms-md-1',
    'ml-md-2' => 'ms-md-2',
    'ml-md-3' => 'ms-md-3',
    'ml-md-4' => 'ms-md-4',
    'ml-md-5' => 'ms-md-5',
    'ml-md-6' => 'ms-md-6',
    'ml-md-7' => 'ms-md-7',
    'ml-md-8' => 'ms-md-8',
    'ml-md-9' => 'ms-md-9',
    'ml-md-10' => 'ms-md-10',
    'ml-md-20' => 'ms-md-20',
    'ml-md-30' => 'ms-md-30',
    'ml-md-auto' => 'ms-md-auto',
    'mr-md-0' => 'me-md-0',
    'mr-md-1' => 'me-md-1',
    'mr-md-2' => 'me-md-2',
    'mr-md-3' => 'me-md-3',
    'mr-md-4' => 'me-md-4',
    'mr-md-5' => 'me-md-5',
    'mr-md-6' => 'me-md-6',
    'mr-md-7' => 'me-md-7',
    'mr-md-8' => 'me-md-8',
    'mr-md-9' => 'me-md-9',
    'mr-md-10' => 'me-md-10',
    'mr-md-20' => 'me-md-20',
    'mr-md-30' => 'me-md-30',
    'mr-md-auto' => 'me-md-auto',
    'ml-lg-0' => 'ms-lg-0',
    'ml-lg-1' => 'ms-lg-1',
    'ml-lg-2' => 'ms-lg-2',
    'ml-lg-3' => 'ms-lg-3',
    'ml-lg-4' => 'ms-lg-4',
    'ml-lg-5' => 'ms-lg-5',
    'ml-lg-6' => 'ms-lg-6',
    'ml-lg-7' => 'ms-lg-7',
    'ml-lg-8' => 'ms-lg-8',
    'ml-lg-9' => 'ms-lg-9',
    'ml-lg-10' => 'ms-lg-10',
    'ml-lg-20' => 'ms-lg-20',
    'ml-lg-30' => 'ms-lg-30',
    'ml-lg-auto' => 'ms-lg-auto',
    'mr-lg-0' => 'me-lg-0',
    'mr-lg-1' => 'me-lg-1',
    'mr-lg-2' => 'me-lg-2',
    'mr-lg-3' => 'me-lg-3',
    'mr-lg-4' => 'me-lg-4',
    'mr-lg-5' => 'me-lg-5',
    'mr-lg-6' => 'me-lg-6',
    'mr-lg-7' => 'me-lg-7',
    'mr-lg-8' => 'me-lg-8',
    'mr-lg-9' => 'me-lg-9',
    'mr-lg-10' => 'me-lg-10',
    'mr-lg-20' => 'me-lg-20',
    'mr-lg-30' => 'me-lg-30',
    'mr-lg-auto' => 'me-lg-auto',
    'ml-xl-0' => 'ms-xl-0',
    'ml-xl-1' => 'ms-xl-1',
    'ml-xl-2' => 'ms-xl-2',
    'ml-xl-3' => 'ms-xl-3',
    'ml-xl-4' => 'ms-xl-4',
    'ml-xl-5' => 'ms-xl-5',
    'ml-xl-6' => 'ms-xl-6',
    'ml-xl-7' => 'ms-xl-7',
    'ml-xl-8' => 'ms-xl-8',
    'ml-xl-9' => 'ms-xl-9',
    'ml-xl-10' => 'ms-xl-10',
    'ml-xl-20' => 'ms-xl-20',
    'ml-xl-30' => 'ms-xl-30',
    'ml-xl-auto' => 'ms-xl-auto',
    'mr-xl-0' => 'me-xl-0',
    'mr-xl-1' => 'me-xl-1',
    'mr-xl-2' => 'me-xl-2',
    'mr-xl-3' => 'me-xl-3',
    'mr-xl-4' => 'me-xl-4',
    'mr-xl-5' => 'me-xl-5',
    'mr-xl-6' => 'me-xl-6',
    'mr-xl-7' => 'me-xl-7',
    'mr-xl-8' => 'me-xl-8',
    'mr-xl-9' => 'me-xl-9',
    'mr-xl-10' => 'me-xl-10',
    'mr-xl-20' => 'me-xl-20',
    'mr-xl-30' => 'me-xl-30',
    'mr-xl-auto' => 'me-xl-auto',
    // Padding class mappings.
    'pl-0' => 'ps-0',
    'pl-1' => 'ps-1',
    'pl-2' => 'ps-2',
    'pl-3' => 'ps-3',
    'pl-4' => 'ps-4',
    'pl-5' => 'ps-5',
    'pl-6' => 'ps-6',
    'pl-7' => 'ps-7',
    'pl-8' => 'ps-8',
    'pl-9' => 'ps-9',
    'pl-10' => 'ps-10',
    'pl-20' => 'ps-20',
    'pl-30' => 'ps-30',
    'pr-0' => 'pe-0',
    'pr-1' => 'pe-1',
    'pr-2' => 'pe-2',
    'pr-3' => 'pe-3',
    'pr-4' => 'pe-4',
    'pr-5' => 'pe-5',
    'pr-6' => 'pe-6',
    'pr-7' => 'pe-7',
    'pr-8' => 'pe-8',
    'pr-9' => 'pe-9',
    'pr-10' => 'pe-10',
    'pr-20' => 'pe-20',
    'pr-30' => 'pe-30',
    'pl-sm-0' => 'ps-sm-0',
    'pl-sm-1' => 'ps-sm-1',
    'pl-sm-2' => 'ps-sm-2',
    'pl-sm-3' => 'ps-sm-3',
    'pl-sm-4' => 'ps-sm-4',
    'pl-sm-5' => 'ps-sm-5',
    'pl-sm-6' => 'ps-sm-6',
    'pl-sm-7' => 'ps-sm-7',
    'pl-sm-8' => 'ps-sm-8',
    'pl-sm-9' => 'ps-sm-9',
    'pl-sm-10' => 'ps-sm-10',
    'pl-sm-20' => 'ps-sm-20',
    'pl-sm-30' => 'ps-sm-30',
    'pr-sm-0' => 'pe-sm-0',
    'pr-sm-1' => 'pe-sm-1',
    'pr-sm-2' => 'pe-sm-2',
    'pr-sm-3' => 'pe-sm-3',
    'pr-sm-4' => 'pe-sm-4',
    'pr-sm-5' => 'pe-sm-5',
    'pr-sm-6' => 'pe-sm-6',
    'pr-sm-7' => 'pe-sm-7',
    'pr-sm-8' => 'pe-sm-8',
    'pr-sm-9' => 'pe-sm-9',
    'pr-sm-10' => 'pe-sm-10',
    'pr-sm-20' => 'pe-sm-20',
    'pr-sm-30' => 'pe-sm-30',
    'pl-md-0' => 'ps-md-0',
    'pl-md-1' => 'ps-md-1',
    'pl-md-2' => 'ps-md-2',
    'pl-md-3' => 'ps-md-3',
    'pl-md-4' => 'ps-md-4',
    'pl-md-5' => 'ps-md-5',
    'pl-md-6' => 'ps-md-6',
    'pl-md-7' => 'ps-md-7',
    'pl-md-8' => 'ps-md-8',
    'pl-md-9' => 'ps-md-9',
    'pl-md-10' => 'ps-md-10',
    'pl-md-20' => 'ps-md-20',
    'pl-md-30' => 'ps-md-30',
    'pr-md-0' => 'pe-md-0',
    'pr-md-1' => 'pe-md-1',
    'pr-md-2' => 'pe-md-2',
    'pr-md-3' => 'pe-md-3',
    'pr-md-4' => 'pe-md-4',
    'pr-md-5' => 'pe-md-5',
    'pr-md-6' => 'pe-md-6',
    'pr-md-7' => 'pe-md-7',
    'pr-md-8' => 'pe-md-8',
    'pr-md-9' => 'pe-md-9',
    'pr-md-10' => 'pe-md-10',
    'pr-md-20' => 'pe-md-20',
    'pr-md-30' => 'pe-md-30',
    'pl-lg-0' => 'ps-lg-0',
    'pl-lg-1' => 'ps-lg-1',
    'pl-lg-2' => 'ps-lg-2',
    'pl-lg-3' => 'ps-lg-3',
    'pl-lg-4' => 'ps-lg-4',
    'pl-lg-5' => 'ps-lg-5',
    'pl-lg-6' => 'ps-lg-6',
    'pl-lg-7' => 'ps-lg-7',
    'pl-lg-8' => 'ps-lg-8',
    'pl-lg-9' => 'ps-lg-9',
    'pl-lg-10' => 'ps-lg-10',
    'pl-lg-20' => 'ps-lg-20',
    'pl-lg-30' => 'ps-lg-30',
    'pr-lg-0' => 'pe-lg-0',
    'pr-lg-1' => 'pe-lg-1',
    'pr-lg-2' => 'pe-lg-2',
    'pr-lg-3' => 'pe-lg-3',
    'pr-lg-4' => 'pe-lg-4',
    'pr-lg-5' => 'pe-lg-5',
    'pr-lg-6' => 'pe-lg-6',
    'pr-lg-7' => 'pe-lg-7',
    'pr-lg-8' => 'pe-lg-8',
    'pr-lg-9' => 'pe-lg-9',
    'pr-lg-10' => 'pe-lg-10',
    'pr-lg-20' => 'pe-lg-20',
    'pr-lg-30' => 'pe-lg-30',
    'pl-xl-0' => 'ps-xl-0',
    'pl-xl-1' => 'ps-xl-1',
    'pl-xl-2' => 'ps-xl-2',
    'pl-xl-3' => 'ps-xl-3',
    'pl-xl-4' => 'ps-xl-4',
    'pl-xl-5' => 'ps-xl-5',
    'pl-xl-6' => 'ps-xl-6',
    'pl-xl-7' => 'ps-xl-7',
    'pl-xl-8' => 'ps-xl-8',
    'pl-xl-9' => 'ps-xl-9',
    'pl-xl-10' => 'ps-xl-10',
    'pl-xl-20' => 'ps-xl-20',
    'pl-xl-30' => 'ps-xl-30',
    'pr-xl-0' => 'pe-xl-0',
    'pr-xl-1' => 'pe-xl-1',
    'pr-xl-2' => 'pe-xl-2',
    'pr-xl-3' => 'pe-xl-3',
    'pr-xl-4' => 'pe-xl-4',
    'pr-xl-5' => 'pe-xl-5',
    'pr-xl-6' => 'pe-xl-6',
    'pr-xl-7' => 'pe-xl-7',
    'pr-xl-8' => 'pe-xl-8',
    'pr-xl-9' => 'pe-xl-9',
    'pr-xl-10' => 'pe-xl-10',
    'pr-xl-20' => 'pe-xl-20',
    'pr-xl-30' => 'pe-xl-30',
    /***************************************************************************
     * Embed / ratio classes.
     */
    'embed-responsive' => 'ratio',
    'embed-responsive-1by1' => 'ratio-1x1',
    'embed-responsive-4by3' => 'ratio-4x3',
    'embed-responsive-16by9' => 'ratio-16x9',
    'embed-responsive-21by9' => 'ratio-21x9',
  ];

}
