<?php

namespace Drupal\blazy\Media;

use Drupal\blazy\BlazyManagerInterface;
use Drupal\media\MediaInterface;
use GuzzleHttp\Client;

/**
 * Provides extra utilities to work with core Media.
 */
interface BlazyMediaInterface {

  /**
   * Returns the http client service.
   *
   * @return \GuzzleHttp\Client
   *   The http client.
   */
  public function httpClient(): Client;

  /**
   * Returns the blazy manager service.
   *
   * @return \Drupal\blazy\BlazyManagerInterface
   *   The blazy manager.
   */
  public function manager(): BlazyManagerInterface;

  /**
   * Builds the media render which is mostly understood by theme_blazy().
   *
   * @param array $data
   *   The array containing:
   *     - #entity the Media or File entity.
   *     - #settings array.
   *
   * @return array
   *   The renderable array of the media field, or empty if not applicable.
   */
  public function build(array $data): array;

  /**
   * Returns the media render which is partly not understood by theme_blazy().
   *
   * When this output arrives at theme_blazy() as content property, Blazy can no
   * longer work with it. That's why we need to do a relatively similar routine
   * to BlazyManager::preRenderBlazy(), only to a bare mimimum.
   *
   * @param array $build
   *   The array containing:
   *     - #entity the Media or File entity.
   *     - #settings array.
   *
   * @return array
   *   The renderable array of the media field, or empty if not applicable.
   */
  public function view(array $build): array;

  /**
   * Returns a media entity from a file entity.
   *
   * This was normally called by Views field file lacking of Media data, unlike
   * field formatters which are abundant of. Guess works here.
   *
   * @param array $data
   *   The array containing:
   *     - #entity, the File entity.
   *     - #settings array.
   *
   * @return object
   *   The media, or NULL if not applicable.
   */
  public function fromFile(array $data): ?object;

  /**
   * Returns a media entity from a field name.
   *
   * @param object $entity
   *   The entity.
   * @param string $field_name
   *   The field_name to query by.
   * @param array|string $values
   *   The optional values of field_name.
   */
  public function fromField($entity, $field_name, $values = NULL): ?object;

  /**
   * Extracts needed info from a media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param string $view_mode
   *   The view_mode.
   * @param string $langcode
   *   The langcode.
   *
   * @return array
   *   The media info containing metadata and translated entity.
   */
  public function getMetadata(MediaInterface $media, $view_mode, $langcode): array;

  /**
   * Returns a guessed source from a file, normally called by Views field file.
   *
   * As long as you are not being too creative by renaming, or changing
   * fields provided by core, this should be your good friend.
   * This guess work is only needed by Views fields lacking of Media data, as
   * seen at IO/Slick Entity Browser specific with file entities.
   *
   * @param object $file
   *   The file entity.
   *
   * @return string
   *   The media source, limited to some known.
   */
  public function getSource($file): ?string;

  /**
   * Modifies item attributes for iframes if any.
   *
   * This requires at least an image.uri to be a lazyloaded iframe.
   *
   * @param array $item
   *   The renderable array, normally entity.get.view or Views row.rendered.
   * @param array $settings
   *   The settings being modified.
   *
   * @return bool
   *   Returns TRUE if iframeable with some modified settings.
   */
  public function iframeable(array &$item, array &$settings): bool;

  /**
   * Prepares media item data to provide image item.
   *
   * @param array $data
   *   The array containing:
   *     - #entity the Media entity.
   *     - #settings array, etc.
   */
  public function prepare(array &$data): MediaInterface;

  /**
   * Converts input URL into embed URL.
   *
   * @param string $input
   *   The input to modify.
   * @param string $iframe_domain
   *   The iframe_domain from media.settings.
   * @param array $parameters
   *   The optional parameters, normally just autoplay.
   *
   * @return string
   *   The media oembed url.
   */
  public function toEmbedUrl($input, $iframe_domain, array $parameters = []): string;

}
