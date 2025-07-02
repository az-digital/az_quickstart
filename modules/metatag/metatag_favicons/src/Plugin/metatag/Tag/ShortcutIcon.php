<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\LinkRelBase;

/**
 * The Favicons "shortcut icon" meta tag.
 *
 * @MetatagTag(
 *   id = "shortcut_icon",
 *   label = @Translation("Default icon"),
 *   description = @Translation("The traditional favicon, must be either a GIF, ICO, JPG/JPEG or PNG image."),
 *   name = "icon",
 *   group = "favicons",
 *   weight = 1,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ShortcutIcon extends LinkRelBase {

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    // This is the one icon tag that doesn't have a size attribute.
    return ["//link[@rel='{$this->name}' and not(@sizes)]"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    $xpath_strings = [];
    foreach ($values as $value) {
      $xpath_strings[] = "//link[@rel='{$this->name}' and not(@sizes) and @" . $this->htmlValueAttribute . "='{$value}']";
    }
    return $xpath_strings;
  }

}
