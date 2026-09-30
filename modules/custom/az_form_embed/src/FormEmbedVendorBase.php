<?php

declare(strict_types=1);

namespace Drupal\az_form_embed;

use Drupal\Core\Plugin\PluginBase;

/**
 * Reads a vendor's name and example links from its attribute.
 *
 * @see \Drupal\az_form_embed\Attribute\FormEmbedVendor
 */
abstract class FormEmbedVendorBase extends PluginBase implements FormEmbedVendorInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function examples(): array {
    return $this->pluginDefinition['examples'] ?? [];
  }

  /**
   * {@inheritdoc}
   *
   * A vendor overrides this to explain its own codes.
   */
  public function explain(string $reason): string {
    return (string) $this->t('it is not a valid link');
  }

}
