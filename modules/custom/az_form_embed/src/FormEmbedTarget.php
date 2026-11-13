<?php

declare(strict_types=1);

namespace Drupal\az_form_embed;

/**
 * The two addresses a vendor gives for a form it accepted.
 *
 * @see \Drupal\az_form_embed\FormEmbedVendorInterface::resolve()
 */
final class FormEmbedTarget {

  /**
   * Constructs a FormEmbedTarget.
   *
   * @param string $embedUrl
   *   The address the vendor's embed script loads from. Only ever use it as a
   *   script source.
   * @param string $canonicalUrl
   *   The form's plain address, safe to link to.
   */
  public function __construct(
    public readonly string $embedUrl,
    public readonly string $canonicalUrl,
  ) {}

}
