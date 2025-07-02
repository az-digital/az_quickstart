<?php

namespace Drupal\field_group_link\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Link;

/**
 * Provides a link render element for Field Groups.
 *
 * @see \Drupal\Core\Render\Element\Link
 *
 * @RenderElement("field_group_link")
 */
class LinkElement extends Link {

  /**
   * {@inheritdoc}
   */
  public static function preRenderLink($element) {
    // Remove child elements that have been added to $element['#title'].
    // Otherwise they will appear twice on the page.
    foreach (Element::children($element['#title']) as $child) {
      if (isset($element[$child])) {
        unset($element[$child]);
      }
    }
    return parent::preRenderLink($element);
  }

}
