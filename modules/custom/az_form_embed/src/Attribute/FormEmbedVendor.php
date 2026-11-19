<?php

declare(strict_types=1);

namespace Drupal\az_form_embed\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Marks a class as a form embed vendor: a service that hosts forms.
 *
 * Each vendor submodule, such as az_form_embed_slate, puts one class with
 * this attribute in its src/Plugin/FormEmbedVendor folder. Drupal only finds
 * it there while that submodule is on, which is how a site turns a vendor
 * off.
 *
 * @see \Drupal\az_form_embed\FormEmbedVendorManager
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class FormEmbedVendor extends Plugin {

  /**
   * Constructs a FormEmbedVendor attribute.
   *
   * @param string $id
   *   The vendor's machine name, such as slate. It travels in the
   *   component's form prop, so the loader can pick the vendor's handler.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The vendor's name as editors see it, such as Slate.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) Help for editors pasting a link.
   * @param string[] $examples
   *   (optional) Links this vendor accepts. Editors see them when a pasted
   *   link is refused.
   * @param int $weight
   *   (optional) The order vendors are asked in. Lower goes first.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $examples = [],
    public readonly int $weight = 0,
    public readonly ?string $deriver = NULL,
  ) {}

}
