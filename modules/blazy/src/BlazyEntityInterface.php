<?php

namespace Drupal\blazy;

/**
 * Provides common entity utilities to work with field details.
 *
 * This is alternative to Drupal\blazy\BlazyFormatter used outside
 * field managers, such as Views field, or Slick/Entity Browser displays, etc.
 * May be called by formatters expecting a mix of theme_blazy() and entity
 * view builder aka vanilla in blazy ecosytem, or output as is as fallback.
 * Should be named BlazyEntityManagerInterface at 3.x, or leave it, no biggies.
 *
 * @see Drupal\blazy\Field\BlazyEntityReferenceBase
 * @see Drupal\blazy\Plugin\Field\FieldFormatter\BlazyMediaFormatterBase
 */
interface BlazyEntityInterface {

  /**
   * Returns the blazy oembed service.
   *
   * @return \Drupal\blazy\Media\BlazyOEmbedInterface
   *   The blazy oembed.
   */
  public function oembed();

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
   *   The blazy media.
   */
  public function blazyMedia();

  /**
   * Build image/video preview either using theme_blazy(), or view builder.
   *
   * May be called by field formatters expecting OEmbed result to extract
   * ImageItem from Media so that understood by theme_blazy(), else vanilla
   * view by view builder as a fallback. A more optimitic approach saying that
   * theme_blazy() may understand it given enough field info than self::view().
   *
   * @param array $data
   *   The data containing:
   *     - #access, if already checked upstream, otherwise leave it undefined.
   *     - #entity, media, file entity, etc. to be associated to media.
   *     - #item, the ImageItem or fake one for video/audio cover, etc.
   *     - #settings, with view_mode, and anything else to work with, depending
   *       on whether to have vanilla, or selective/ fieldable renderable array.
   *     - fallback, when all fails, probably just entity label.
   *
   * @return array
   *   The renderable array of theme_blazy(), or view builder, else empty array.
   */
  public function build(array $data): array;

  /**
   * Prepare entity once.
   *
   * This class was not designed to deal with multiple entities, but one.
   * Call this method once at the container level for multiple entities.
   *
   * @param array $data
   *   An array of data containing settings, image item, entity, and fallback.
   */
  public function prepare(array &$data): void;

  /**
   * Provides an entity.get.view output, or vanilla entity view.
   *
   * Mostly called by non-field formatters lacking of field info such as
   * BlazyViewsField[File|Media], sub-modules like IO|Slick Browsers, etc. A
   * more pessimistic approach than self::build() saying that theme_blazy()
   * won't understand this, better go with entity.get.view or vanilla entity
   * view in the first place. This might be improved and passed to theme_blazy()
   * or call self:: build() directly when we have field info upstream.
   *
   * @param array $data
   *   The data containing:
   *     - #access, if already checked upstream, otherwise leave it undefined.
   *     - #entity, media or file entity, to be associated to media, or any.
   *     - #settings, with view_mode, and any/nothing else.
   *     - fallback, when all fails, probably just entity label.
   *
   * @return array
   *   The renderable array of the view builder, or empty if not applicable.
   */
  public function view(array $data): array;

}
