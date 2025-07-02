<?php

declare(strict_types=1);

namespace Drupal\paragraphs\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Attribute class for paragraphs behavior plugins.
 *
 * Plugin Namespace: Plugin\paragraphs\Behavior
 *
 * @see \Drupal\paragraphs\ParagraphsBehaviorInterface
 * @see \Drupal\paragraphs\ParagraphsBehaviorManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ParagraphsBehavior extends Plugin {

  /**
   * Constructs a Mail attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) A description of the plugin.
   * @param int $weight
   *   (optional) The plugin weight.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly int $weight = 0,
    public readonly ?string $deriver = NULL,
  ) {}

}
