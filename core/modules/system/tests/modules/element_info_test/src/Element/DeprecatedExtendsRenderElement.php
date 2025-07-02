<?php

namespace Drupal\element_info_test\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementDeprecated;

/**
 * Provides render element that extends deprecated RenderElement for testing.
 *
 * @phpstan-ignore-next-line
 */
#[RenderElement('deprecated_extends_render')]
class DeprecatedExtendsRenderElement extends RenderElementDeprecated {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
