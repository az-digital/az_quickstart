<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * This base plugin allows "link rel" tags with a "sizes" attribute.
 */
abstract class LinkSizesBase extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function output(): array {
    $element = parent::output();

    if ($element) {
      $element['#attributes'] = [
        'rel' => $this->name(),
        'sizes' => $this->iconSize(),
        'href' => $element['#attributes']['href'],
      ];
    }

    return $element;
  }

  /**
   * The dimensions supported by this icon.
   *
   * @return string
   *   A string in the format "XxY" for a given width and height.
   */
  protected function iconSize(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    return ["//link[@rel='{$this->name}' and @sizes='" . $this->iconSize() . "']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    $xpath_strings = [];
    foreach ($values as $value) {
      $xpath_strings[] = "//link[@rel='{$this->name}' and @sizes='" . $this->iconSize() . "' and @" . $this->htmlValueAttribute . "='{$value}']";
    }
    return $xpath_strings;
  }

}
