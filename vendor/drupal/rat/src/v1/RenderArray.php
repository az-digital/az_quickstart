<?php

namespace Drupal\rat\v1;

/**
 * Build and alter render arrays without pain.
 *
 * This general class is agnostic of render array type. To create type-aware
 * builder classes, extend RenderArrayBase.
 *
 * Header, feed, head, headLink:
 * @link https://www.drupal.org/node/2160069
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface::processAttachments
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processAttachments
 */
final class RenderArray extends RenderArrayBase {

  /**
   * Create a generic render array.
   */
  public static function create(): self {
    $build = [];
    return new self($build);
  }

  /**
   * Alter a render array or value.
   *
   * @param mixed $build
   * @return static
   */
  public static function alter(&$build): self {
    $instance = new self($build);
    $instance->value =& $build;
    return $instance;
  }

}
