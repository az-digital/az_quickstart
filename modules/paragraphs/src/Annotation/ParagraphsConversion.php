<?php

namespace Drupal\paragraphs\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ParagraphsConversion annotation object.
 *
 * Paragraphs conversion handles conversion types for Paragraphs.
 *
 * @Annotation
 */
class ParagraphsConversion extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the paragraphs conversion plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The Paragraphs source type where the plugin can be applied.
   *
   * @var string
   */
  public $source_type;

  /**
   * The Paragraphs target types that the Paragraph will be converted to.
   *
   * @var array
   */
  public $target_types = [];

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

}
