<?php

namespace Drupal\blazy\Media;

/**
 * Provides OEmbed integration.
 */
interface BlazyOEmbedInterface {

  /**
   * Returns the Media oEmbed resource fecther.
   */
  public function getResourceFetcher();

  /**
   * Returns the Media oEmbed url resolver fecthers.
   */
  public function getUrlResolver();

  /**
   * Returns the blazy manager service.
   *
   * @return \Drupal\blazy\BlazyManagerInterface
   *   The blazy manager.
   */
  public function blazyManager();

  /**
   * Returns the blazy media.
   *
   * @return \Drupal\blazy\Media\BlazyMediaInterface
   *   The blazy manager.
   */
  public function blazyMedia();

  /**
   * Returns the oEmbed provider based on the given media input url.
   *
   * @param string $input
   *   The input url.
   *
   * @return \Drupal\media\OEmbed\Provider|null
   *   The oEmbed provider if available, or NULL.
   */
  public function getProvider($input): ?object;

  /**
   * Returns the oEmbed Resource based on the given media input url.
   *
   * @param string $input
   *   The input url.
   *
   * @return \Drupal\media\OEmbed\Resource|null
   *   The oEmbed resource.
   */
  public function getResource($input): ?object;

  /**
   * Builds media-related settings based on the given media input url.
   *
   * Accepts various sources: BlazyFilter, BlazyViewsFieldFile, BlazyEntity,
   * regular Media/ OEmbed related formatters, and the deprecated VEF.
   * Old VEF/BVEF might have no expected entity to associate with.
   *
   * @param array $build
   *   The array being modified containing: content, settings and image item.
   *   Or just settings content for old deprecated approach.
   *
   * @todo should be at non-static BlazyMedia at 4.x, if too late for 3.x.
   * @todo add a return to avoid potential issues with references at 3.x.
   */
  public function build(array &$build): void;

  /**
   * Checks the given input URL.
   *
   * @param array $settings
   *   The settings being modified.
   * @param string $input
   *   The input to modify.
   *
   * @return string
   *   The modified input url.
   */
  public function checkInputUrl(array &$settings, $input): ?string;

  /**
   * Returns external image item from resource for BlazyFilter or VEF.
   *
   * The settings fallbacks are preserved for minimal BVEF compat. This method
   * allows VEF to have TITLE or ALT for media related displays.
   *
   * @param array $settings
   *   The settings being modified.
   * @param bool $fallback
   *   If it is as fallback to fetch image, else just global definitions.
   *
   * @return object
   *   The fake image item, or null if failed, or not a fallback.
   */
  public function getThumbnail(array &$settings, $fallback = TRUE): ?object;

  /**
   * Converts input URL into embed URL.
   *
   * @param object $blazies
   *   The \Drupal\blazy\BlazySettings object.
   * @param string $input
   *   The input to modify.
   * @param array $params
   *   The optional parameters, normally just autoplay.
   *
   * @return string
   *   The media oembed url.
   */
  public function toEmbedUrl($blazies, $input, array $params = []): string;

}
