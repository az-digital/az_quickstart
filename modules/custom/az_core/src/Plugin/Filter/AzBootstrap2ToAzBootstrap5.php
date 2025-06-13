<?php

namespace Drupal\az_core\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Core\Render\Markup;
use Masterminds\HTML5;

/**
 * Provides a filter to convert DOI identifiers to links.
 */
#[Filter(
  id: "az_bootstrap2_to_az_bootstrap5",
  title: new TranslatableMarkup("Convert AZ Bootstrap 2 to AZ Bootstrap 5"),
  description: new TranslatableMarkup("This filter converts AZ Bootstrap 2 classes to AZ Bootstrap 5 classes."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
)]
class AzBootstrap2ToAzBootstrap5 extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $html5 = new HTML5();
    $dom = $html5->loadHTML($text);

    if (!$dom || !$dom->documentElement) {
      return new FilterProcessResult($text);
    }

    $xpath = new \DOMXPath($dom);

    // List of data-* attributes from Bootstrap 4 that should become data-bs-*.
    $data_attributes_to_convert = [
      'toggle',
      'target',
      'dismiss',
      'ride',
      'slide',
      'interval',
      'keyboard',
      'backdrop',
      'html',
      'placement',
      'trigger',
      'content',
      'container',
      'boundary',
      'offset',
      'delay',
      'animation',
      'autohide',
      'reference',
      'method',
      'pause',
      'template',
      'wrap',
      'touch',
    ];

    foreach ($dom->getElementsByTagName('*') as $element) {
      foreach (iterator_to_array($node->attributes) as $attr) {
        if (preg_match('/^data-(' . implode('|', array_map('preg_quote', $data_attributes_to_convert)) . ')$/', $attr->name, $matches)) {
          $new_name = 'data-bs-' . $matches[1];
          $node->setAttribute($new_name, $attr->value);
          $node->removeAttribute($attr->name);
        }
      }
    }

    // Map Bootstrap 2 classes to Bootstrap 5 classes.
    $class_map = [
      'badge-primary' => 'text-bg-primary',
      'badge-secondary' => 'text-bg-secondary',
      'form-group' => 'mb-3',
      'form-inline' => 'd-flex align-items-center',
      'custom-select' => 'form-select',
      'sr-only' => 'visually-hidden',
      'text-left' => 'text-start',
      'text-right' => 'text-end',
      // Add more as needed...
    ];

    foreach ($xpath->query('//*[@class]') as $node) {
      $classes = explode(' ', $node->getAttribute('class'));
      $updated = false;

      foreach ($classes as &$class) {
        if (isset($class_map[$class])) {
          $class = $class_map[$class];
          $updated = true;
        }
      }

      if ($updated) {
        $node->setAttribute('class', implode(' ', $classes));
      }
    }

    $output = $html5->saveHTML($dom->documentElement);

    return new FilterProcessResult(Markup::create($output));
  }

}

