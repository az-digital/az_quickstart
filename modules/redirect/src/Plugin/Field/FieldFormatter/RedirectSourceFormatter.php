<?php

namespace Drupal\redirect\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Implementation of the 'redirect_source' formatter.
 *
 * @FieldFormatter(
 *   id = "redirect_source",
 *   label = @Translation("Redirect Source"),
 *   field_types = {
 *     "redirect_source",
 *   }
 * )
 */
class RedirectSourceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => urldecode($item->getUrl()->toString()),
      ];
    }

    return $elements;
  }

}
