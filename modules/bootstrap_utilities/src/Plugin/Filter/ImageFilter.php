<?php

namespace Drupal\bootstrap_utilities\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Add Bootstrap Class to any image.
 *
 * @Filter(
 *   id = "bootstrap_utilities_image_filter",
 *   title = @Translation("Bootstrap Utilities - Responsive Image Class"),
 *   description = @Translation("This filter will allow you to add a default Bootstrap class to each image"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE
 * )
 */
class ImageFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'img') !== FALSE) {

      $setting_classes = [];
      $setting_classes[] = 'img-fluid';

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);

      $img_elements = $xpath->query('//img');
      if (!is_null($img_elements)) {
        foreach ($img_elements as $element) {
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
