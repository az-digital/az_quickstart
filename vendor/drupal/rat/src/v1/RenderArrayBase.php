<?php
/** @noinspection PhpMissingReturnTypeInspection */

namespace Drupal\rat\v1;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderableInterface;

abstract class RenderArrayBase implements RenderableInterface {

  /**
   * The value, array or other.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Constructor.
   *
   * @param mixed  $build
   *   The build. May be a non-array due to how ::get() works.
   */
  protected function __construct(&$build) {
    $this->value =& $build;
  }

  /**
   * Get the render array or value.
   *
   * @return mixed
   */
  public function toRenderable() {
    return $this->value;
  }

  /**
   * Get the render array or value as reference.
   *
   * @return mixed
   */
  public function &getValue() {
    return $this->value;
  }

  /**
   * Get or create a key, possibly nested (value must be null or array).
   *
   * @param string ...$keys
   *   The nested keys.
   * @return $this
   */
  public function get(string ...$keys) {
    $build =& $this->value;
    foreach ($keys as $key) {
      $build =& $build[$key];
    }
    return RenderArray::alter($build);
  }

  /**
   * Get or create nested key, separated by dots.
   *
   * @param string $path
   *   The key path separated by dots.
   * @return $this
   */
  public function getDotted(string $path) {
    $keys = !$path ? [] : explode('.', $path);
    return $this->get(...$keys);
  }

  /**
   * Set the value.
   *
   * @param $value
   *   The new value.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|null $cacheability
   *   Cacheability of the value.
   * @param $forceOverwrite
   *   Force overwrite of non-null values (otherwise throws).
   * @return $this
   */
  public function setValue($value, ?CacheableDependencyInterface $cacheability = NULL, $forceOverwrite = FALSE) {
    if (isset($this->value) && !$forceOverwrite) {
      throw new \LogicException('Overwriting an already set build can overwrite cacheability thus cause security issues. If you are sure you want that, use the $forceOverwrite parameter.');
    }
    else {
      $this->value = $value;
      $this->addCacheability($cacheability);
    }
    return $this;
  }

  /**
   * Check if value is non-null.
   *
   * @return bool
   */
  public function isset(): bool {
    return isset($this->value);
  }

  /**
   * Indicates whether the build is empty, ignoring cacheability.
   *
   * @return bool
   */
  public function isEmptyBuild(): bool {
    return is_array($this->value) ? Element::isEmpty($this->value) : !$this->isset();
  }

  /**
   * Get an array of children RenderArrays.
   *
   * @return \Drupal\rat\v1\RenderArray[]
   *   The children as RenderArray objects.
   */
  public function children(): array {
    $children = Element::children($this->value);
    $children = array_combine($children, $children);
    return array_map(fn($key) => $this->get($key), $children);
  }

  /**
   * Check if value is array-like.
   *
   * @return bool
   */
  private function isArrayLike(): bool {
    return !isset($this->value) || is_array($this->value);
  }

  /**
   * Add another, like $array[].
   *
   * @return $this
   */
  public function addAnother() {
    if (!$this->isArrayLike()) {
      throw new \RuntimeException("Can only addAnother to arrayLike value.");
    }
    return RenderArray::alter($this->value[]);
  }

  /**
   * Attach library
   *
   * @link https://www.drupal.org/docs/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml#render-array
   *
   * @param string $library
   *   The library, like 'your_module/library_name'.
   * @return $this
   */
  public function attachLibrary(string $library) {
    $this->value['#attached']['library'][] = $library;
    return $this;
  }

  /**
   * Attach drupalSettings
   *
   * @link https://www.drupal.org/docs/creating-modules/adding-assets-css-js-to-a-drupal-module-via-librariesyml#render-array
   *
   * @param string $key
   *   The drupalSettings key.
   * @param mixed $value
   *   The drupalSettings value.
   * @return $this
   *
   * @link https://www.drupal.org/node/2274843#configurable
   */
  public function attachDrupalSettings(string $key, $value) {
    $this->value['#attached']['drupalSettings'][$key] = $value;
    return $this;
  }

  /**
   * Attach a HTML header.
   *
   * @param string $name
   *   The header name, 'status' is treated specially and used as http status
   *   code.
   * @param string $value
   *   The header value.
   * @param bool $replace
   *   (optional) Whether to replace a current value with the new one, or add
   *   it to the others. If the value is not replaced, it will be appended,
   *   resulting in a header like this: 'Header: value1,value2'
   *
   * @return $this
   *
   * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::setHeaders
   */
  public function attachHeader(string $name, string $value, bool $replace = FALSE) {
    $this->value['#attached']['http_header'][] = [$name, $value, $replace];
    return $this;
  }

  /**
   * Attach a feed.
   *
   * @param string $href
   *  The feed href.
   * @param string|null $title
   *   The feed title.
   *
   * @return $this
   *
   * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processFeed
   */
  public function attachFeed(string $href, string $title = NULL) {
    $this->value['#attached']['feed'][] = [$href, $title];
    return $this;
  }

  /**
   * Attach a head link.
   *
   * @param string $href
   *   The link href.
   * @param string $rel
   *   The link rel.
   * @param string|null $title
   *   The link title, optional.
   * @param string|null $type
   *   The link type, optional.
   * @param string|null $hreflang
   *   The link hreflang, optional.
   * @param bool $shouldAddHeader
   *   A boolean specifying whether the link should also be a Link: HTTP header.
   * @param array $moreAttributes
   *   Optionally more link attributes.
   *
   * @return $this
   *
   * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processHtmlHeadLink
   * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link
   */
  public function attachHeadLink(string $href, string $rel, string $title = NULL, string $type = NULL, string $hreflang = NULL, bool $shouldAddHeader = FALSE, array $moreAttributes = []) {
    $linkAttributes = [
      'href' => $href,
      'rel' => $rel,
      'title' => $title,
      'type' => $type,
      'hreflang' => $hreflang,
    ] + $moreAttributes;
    $this->value['#attached']['html_head_link'][] = [$linkAttributes, $shouldAddHeader];
    return $this;
  }

  /**
   * Attach head element.
   *
   * @param string $key
   *   A unique key for the head content. Re-attaching builds with the same key
   *   are ignored.
   * @param array $build
   *   The build array to attach. If no #type is given, 'html_tag' is assumed.
   *
   * @return $this
   *
   * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processHtmlHead
   */
  public function attachHead(string $key, array $build) {
    $this->value['#attached']['html_head'][] = [$build, $key];
    return $this;
  }

  /**
   * Add cacheability.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|null $cacheability
   *   A cacheability, or null to add nothing.
   * @return $this
   */
  public function addCacheability(?CacheableDependencyInterface $cacheability) {
    if ($cacheability) {
      CacheableMetadata::createFromRenderArray($this->value)
        ->addCacheableDependency($cacheability)
        ->applyTo($this->value);
    }
    return $this;
  }

  /**
   * Set uncacheable by adding max-age zero cacheability.
   *
   * @return $this
   */
  public function setUncacheable() {
    $this->addCacheability((new CacheableMetadata())->setCacheMaxAge(0));
    return $this;
  }

  /**
   * Get build access as AccessResult object including cacheability.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function getAccessResult(): AccessResult {
    $rawAccessValue = $this->value['#access'] ?? TRUE;
    if ($rawAccessValue instanceof AccessResultInterface) {
      return AccessResult::allowedIf($rawAccessValue->isAllowed())
        ->addCacheableDependency($rawAccessValue);
    }
    else {
      // Core treats everything that is not FALSE as TRUE. Imitate that.
      // @see \Drupal\Core\Render\Renderer::doRender
      return AccessResult::allowedIf($rawAccessValue !== FALSE);
    }
  }

  /**
   * Restrict access via access value and cacheability.
   *
   * @param bool $access
   *   The access value.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|null $cacheability
   *   The access cacheability, to not forget it.
   * @return $this
   */
  public function restrictAccess(bool $access, ?CacheableDependencyInterface $cacheability) {
    return $this->restrictAccessResult(AccessResult::allowedIf($access)->addCacheableDependency($cacheability ?? new CacheableMetadata()));
  }

  /**
   * Restrict access via AccessResult object.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $accessResult
   *   The access result object.
   * @return $this
   */
  public function restrictAccessResult(AccessResultInterface $accessResult) {
    return $this->setAccessResult($this->getAccessResult()->andIf($accessResult));
  }

  /**
   * Set #access = false for this build.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface|null $cacheability
   *   The access cacheability, to not forget it.
   * @return $this
   */
  public function setNoAccess(?CacheableDependencyInterface $cacheability) {
    return $this->restrictAccess(FALSE, $cacheability);
  }

  /**
   * Set access result (internal).
   *
   * @param \Drupal\Core\Access\AccessResult $accessResult
   * @return $this
   */
  private function setAccessResult(AccessResult $accessResult) {
    if ($this->isEmptyCacheability($accessResult)) {
      // Refrain from optimizing #access=true to no #access, it makes debugging
      // harder with no actual benefit.
      $this->value['#access'] = $accessResult->isAllowed();
    }
    else {
      $this->value['#access'] = $accessResult;
    }
    return $this;
  }

  /**
   * Check if cacheability is empty.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $cacheability
   * @return bool
   */
  private function isEmptyCacheability(CacheableDependencyInterface $cacheability): bool {
    return $cacheability->getCacheMaxAge() === Cache::PERMANENT
      && !$cacheability->getCacheContexts()
      && !$cacheability->getCacheTags();
  }

}
