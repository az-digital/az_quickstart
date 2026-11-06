<?php

declare(strict_types=1);

namespace Drupal\az_form_embed;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * A service that hosts forms, such as Slate.
 *
 * The Form embed media type stores only a link. Its vendor is worked out from
 * that link each time, by asking each turned-on vendor in turn.
 *
 * @see \Drupal\az_form_embed\Attribute\FormEmbedVendor
 * @see \Drupal\az_form_embed\FormEmbedVendorManager
 */
interface FormEmbedVendorInterface extends PluginInspectionInterface {

  /**
   * Returns the vendor's name as editors see it, such as Slate.
   */
  public function label(): string;

  /**
   * Returns links this vendor accepts, to show editors as examples.
   *
   * @return string[]
   *   Example links.
   */
  public function examples(): array;

  /**
   * Checks whether a link looks like this vendor's, even if it isn't valid.
   *
   * This is looser than resolve(). It decides which vendor's help an editor
   * sees when a link is refused. For example, a Slate link with a person
   * parameter is still Slate's, so the editor hears why Slate refused it.
   *
   * @param string $url
   *   The link an editor pasted.
   *
   * @return bool
   *   TRUE if the link looks like it belongs to this vendor.
   */
  public function claims(string $url): bool;

  /**
   * Checks a link and turns it into the addresses the component needs.
   *
   * This is the security check. What it returns goes into a script tag.
   *
   * @param string $url
   *   The link an editor pasted.
   * @param string|null $reason
   *   (optional) Set to a short code saying why the link was refused, such
   *   as bad_host. It's for logs. explain() turns it into words for editors.
   *
   * @return \Drupal\az_form_embed\FormEmbedTarget|null
   *   The addresses, or NULL if the link was refused.
   */
  public function resolve(string $url, ?string &$reason = NULL): ?FormEmbedTarget;

  /**
   * Explains a refusal code from resolve() in words an editor understands.
   *
   * @param string $reason
   *   The code resolve() set.
   *
   * @return string
   *   A clause that finishes "This link can't be used because ...", such as
   *   "it must start with https://".
   */
  public function explain(string $reason): string;

}
