<?php

namespace Drupal\metatag_favicons\Plugin\metatag\Tag;

/**
 * The Favicons "apple-touch-icon-precomposed_114x114" meta tag.
 *
 * This is basically a clone of the non-precomposed class.
 *
 * @MetatagTag(
 *   id = "apple_touch_icon_precomposed_114x114",
 *   label = @Translation("Apple touch icon (precomposed): 114px x 114px"),
 *   description = @Translation("A PNG image that is 114px wide by 114px high. Used with iPhone with @2x display running iOS <= 6."),
 *   name = "apple-touch-icon-precomposed",
 *   group = "favicons",
 *   weight = 18,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AppleTouchIconPrecomposed114x114 extends AppleTouchIcon114x114 {
  // Nothing here yet. Just a placeholder class for a plugin.
}
