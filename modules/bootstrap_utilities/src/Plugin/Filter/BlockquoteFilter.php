<?php

namespace Drupal\bootstrap_utilities\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Add Bootstrap Class to any blockquote.
 *
 * @Filter(
 *   id = "bootstrap_utilities_blockquote_filter",
 *   title = @Translation("Bootstrap Utilities - Blockquote Classes"),
 *   description = @Translation("This filter will allow you to add default Bootstrap classes to a blockquote"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class BlockquoteFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'blockquote') !== FALSE) {
      $setting_classes = [];
      $setting_classes[] = 'blockquote';

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      $blockquote_elements = $xpath->query('//blockquote');
      if (!is_null($blockquote_elements)) {
        foreach ($blockquote_elements as $element) {
          $existing_classes = [];
          $with_setting_classes = [];
          if ($element->getAttribute('class')) {
            $existing_classes[] = $element->getAttribute('class');
          }
          $with_setting_classes = array_unique(array_merge($existing_classes, $setting_classes), SORT_REGULAR);
          $all_classes = implode(' ', $with_setting_classes);
          $element->setAttribute('class', $all_classes);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
