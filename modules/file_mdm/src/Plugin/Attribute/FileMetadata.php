<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The FileMetadata attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FileMetadata extends Plugin {

  /**
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The title of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $help
   *   An informative description of the plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly TranslatableMarkup $help,
  ) {}

}
