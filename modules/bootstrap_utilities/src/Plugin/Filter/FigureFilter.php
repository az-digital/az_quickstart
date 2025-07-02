<?php

namespace Drupal\bootstrap_utilities\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Add Bootstrap class to any figure.
 *
 * @Filter(
 *   id = "bootstrap_utilities_figure_filter",
 *   title = @Translation("Bootstrap Utilities - Figure Classes"),
 *   description = @Translation("This filter will allow you to add default Bootstrap classes to a figure and its caption."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class FigureFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'figure') !== FALSE) {
      $fig_setting_classes = [];
      $cap_setting_classes = [];

      $fig_setting_classes[] = 'figure';
      $cap_setting_classes[] = 'figure-caption';

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      $figure_elements = $xpath->query('//figure');
      if (!is_null($figure_elements)) {
        foreach ($figure_elements as $element) {
          $existing_classes = [];
          $with_setting_classes = [];
          if ($element->getAttribute('class')) {
            $existing_classes[] = $element->getAttribute('class');
          }
          $with_setting_classes = array_unique(array_merge($existing_classes, $fig_setting_classes), SORT_REGULAR);
          $all_classes = implode(' ', $with_setting_classes);
          $element->setAttribute('class', $all_classes);
        }
      }

      $figcaption_elements = $xpath->query('//figcaption');
      if (!is_null($figcaption_elements)) {
        foreach ($figcaption_elements as $element) {
          $existing_classes = [];
          $with_setting_classes = [];
          if ($element->getAttribute('class')) {
            $existing_classes[] = $element->getAttribute('class');
          }
          $with_setting_classes = array_unique(array_merge($existing_classes, $cap_setting_classes), SORT_REGULAR);
          $all_classes = implode(' ', $with_setting_classes);
          $element->setAttribute('class', $all_classes);
        }
      }

      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
