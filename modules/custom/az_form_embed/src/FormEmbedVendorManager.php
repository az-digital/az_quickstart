<?php

declare(strict_types=1);

namespace Drupal\az_form_embed;

use Drupal\az_form_embed\Attribute\FormEmbedVendor;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Finds the form embed vendors that are turned on, and asks them about links.
 *
 * Each vendor is a class in a submodule's src/Plugin/FormEmbedVendor folder.
 * Drupal looks there only in modules that are on, so turning a vendor's
 * submodule off removes its vendor.
 *
 * It follows core's text filter manager, but without that manager's
 * alterInfo() call. Don't add one. Rationale: alterInfo() creates an alter
 * hook, which lets any module on a site change a vendor's definition, and the
 * vendors decide which links reach a script tag.
 *
 * @see \Drupal\filter\FilterPluginManager
 */
class FormEmbedVendorManager extends DefaultPluginManager {

  /**
   * The cache tag cleared whenever the list of vendors changes.
   *
   * Anything that depends on which vendors are on, like the Canvas hook in
   * az_form_embed.module, should carry this tag.
   */
  public const CACHE_TAG = 'az_form_embed_vendor_plugins';

  /**
   * Constructs a FormEmbedVendorManager.
   *
   * @param \Traversable $namespaces
   *   The namespaces of the modules that are on.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache for the vendor definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FormEmbedVendor', $namespaces, $module_handler, FormEmbedVendorInterface::class, FormEmbedVendor::class);
    $this->setCacheBackend($cache_backend, 'az_form_embed_vendor_plugins', [self::CACHE_TAG]);
  }

  /**
   * Returns every turned-on vendor, lowest weight first.
   *
   * @return \Drupal\az_form_embed\FormEmbedVendorInterface[]
   *   The vendors, keyed by id.
   */
  public function getVendors(): array {
    $definitions = $this->getDefinitions();
    uasort($definitions, fn (array $a, array $b) => ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0));
    $vendors = [];
    foreach (array_keys($definitions) as $id) {
      $vendors[$id] = $this->createInstance($id);
    }
    return $vendors;
  }

  /**
   * Returns the first vendor a link looks like it belongs to.
   *
   * @param string $url
   *   The link an editor pasted.
   *
   * @return \Drupal\az_form_embed\FormEmbedVendorInterface|null
   *   The vendor, or NULL if no turned-on vendor claims the link.
   */
  public function getClaimingVendor(string $url): ?FormEmbedVendorInterface {
    foreach ($this->getVendors() as $vendor) {
      if ($vendor->claims($url)) {
        return $vendor;
      }
    }
    return NULL;
  }

  /**
   * Checks a link and returns what the Form embed component needs for it.
   *
   * @param string $url
   *   The link an editor pasted.
   * @param string|null $reason
   *   (optional) Set to why the link was refused, when it was.
   *
   * @return array{vendor: string, embed_url: string, canonical_url: string}|null
   *   The values, keyed like the component's form prop, without its label.
   *   NULL if no turned-on vendor accepts the link.
   */
  public function resolve(string $url, ?string &$reason = NULL): ?array {
    $vendor = $this->getClaimingVendor($url);
    if ($vendor === NULL) {
      $reason = 'no form provider that is turned on handles this link';
      return NULL;
    }
    $target = $vendor->resolve($url, $reason);
    if ($target === NULL) {
      return NULL;
    }
    return [
      'vendor' => $vendor->getPluginId(),
      'embed_url' => $target->embedUrl,
      'canonical_url' => $target->canonicalUrl,
    ];
  }

}
