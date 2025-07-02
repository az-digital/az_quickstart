<?php

namespace Drupal\blazy\Media;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Url;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Utility\CheckItem;
use Drupal\blazy\internals\Internals;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\MediaInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides extra utilities to work with core Media.
 *
 * This class makes it possible to have a mixed display of all media entities,
 * useful for Blazy Grid, Slick Carousel, GridStack contents as mixed media.
 * This approach is alternative to regular preprocess overrides, still saner
 * than iterating over unknown like template_preprocess_media_BLAH, etc.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Media integration is being reworked.
 *
 * @todo rework this for core Media, and refine for theme_blazy(). Two big TODOs
 * for the next releases are:
 * - TODO: replace ImageItem references into just $settings
 * - DONE, 2.17: convert this into non-static, move most BlazyOEmbed stuffs.
 * Not urgent, the important is to make it just work with minimal regressions.
 * @todo recap similiraties and make them plugins.
 */
class BlazyMedia implements BlazyMediaInterface {

  /**
   * The http client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Constructs a BlazyFormatter instance.
   */
  public function __construct(
    BlazyManagerInterface $manager,
    Client $http_client,
    IFrameUrlHelper $iframe_url_helper,
  ) {
    $this->manager = $manager;
    $this->httpClient = $http_client;
    $this->iFrameUrlHelper = $iframe_url_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.manager'),
      $container->get('http_client'),
      $container->get('media.oembed.iframe_url_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function httpClient(): Client {
    return $this->httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function manager(): BlazyManagerInterface {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $data): array {
    $manager  = $this->manager;
    $settings = &$data['#settings'];

    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $item */
    $item = $manager->toHashtag($data, 'item', NULL);

    // @todo recheck BlazyOEmbed::fromMedia() if already covered since 2.17.
    if (!$item) {
      // Re-defined, needed downstream by local video, etc.
      $settings['view_mode'] = $settings['view_mode'] ?? 'default';
      $data['content'][] = $this->view($data);
    }

    $blazies = $settings['blazies'];
    $blazies->set('is.denied', empty($data['#access']));

    // Pass it to Blazy for consistent markups.
    unset($data['delta'], $data['fallback']);
    $build = $manager->getBlazy($data);

    // Allows top level elements to load Blazy once rather than per field.
    // This is still here for non-supported Views style plugins, etc.
    // The detached flag just means do not attach libraries.
    if (!$blazies->is('detached') && $load = $manager->attach($settings)) {
      $build['#attached'] = $manager->merge($load, $build, '#attached');
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array $build): array {
    $entity   = $build['#media'] ?? $build['#entity'];
    $settings = &$build['#settings'];
    $item     = $build['#item'] ?? NULL;

    // Ensures the essentials setup early here since it enters theme_blazy() as
    // non-workable content.
    $blazies = $this->manager->preBlazy($build, $item);
    $settings['blazies'] = $blazies;

    // Prevents fatal error with disconnected internet when having ME Facebook,
    // ME SlideShare, resorted to static thumbnails to avoid broken displays.
    $source = $blazies->get('media.source');
    $safe = $this->getSafeSource($source);
    if (!$safe && $input = $blazies->get('media.input_url')) {
      try {
        $this->httpClient->get($input, ['timeout' => 3]);
      }
      catch (\Exception $e) {
        return [];
      }
    }

    // Checks if a file is given, and so convert it to a media entity.
    // video_file is empty from view builder, resorts to entity.get.view.
    if ($entity->getEntityTypeId() == 'file'
      && $media = $this->fromFile($build)) {
      $entity = $media;
    }

    // Local video, FB, Twitter, etc. is rich to be simple due to terracota,
    // can be refined later when Blazy supports more media types better.
    if ($entity instanceof MediaInterface) {
      $view_mode = $settings['view_mode'] ?? 'default';
      $view_mode = $blazies->get('media.view_mode', $view_mode);
      $source_field = $blazies->get('media.source_field');

      // Reset $build, except for #settings, we'll unwrap theme_field() here:
      $build = $entity->get($source_field)->view($view_mode);
      $build['#settings'] = $settings;

      return isset($build[0]) ? $this->unfield($build) : $build;
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fromFile(array $data): ?object {
    $file     = $data['#entity'];
    $settings = &$data['#settings'];

    // In case called outside the workflow.
    $blazies = $this->manager->verifySafely($settings);

    // Seen at IO/Slick Entity Browser specific with file lacking of media data.
    if ($source = $this->getSource($file)) {
      $source = $blazies->get('media.source', $source);
      $source_field = $blazies->get('media.source_field', 'field_media_' . $source);
      $blazies->set('media.source', $source);
      $blazies->set('media.source_field', $source_field);
    }

    if ($name = $blazies->get('media.source_field')) {
      $values = ['fid' => $file->id()];
      return $this->fromField($file, $name, $values);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function fromField($entity, $field_name, $value = NULL): ?object {
    $media = NULL;
    if ($value) {
      // See BlazyOEmbedFormatter::getElements().
      if ($entity->getEntityTypeId() == 'media'
        && $entity->hasField($field_name)) {
        // Not needed with contextual info, but we want a strict check when
        // it is called out of context to match the exact media by its field.
        $valid = FALSE;
        $field = $entity->get($field_name);
        if (is_string($value)) {
          $valid = $field->getString() == $value;
        }
        else {
          $valid = $field->getValue() == $value;
        }
        // We are on the right media entity.
        if ($valid) {
          $media = $entity;
        }
      }
      else {
        // Attempts to fetch the media entity.
        // See self::fromFile().
        $media = $this->manager->loadByProperty($field_name, $value, 'media');
      }
    }
    // At node, paragraphs, etc, having a field which references:
    // two designated types of $stage: MediaInterface and FileInterface.
    // Since 2.10, Main stage is usable as the main display of a Paragraphs,
    // only if the stage is a Media entity and Overlay is left empty. Basically
    // render the Media and replace its parent $entity. This way if it is a
    // video, Media switch will kick in as a Media player or simply an iframe.
    // Old behavior is intact if Overlay is provided as previously designed.
    // Before 2.10, the stage was always made an Image, and required Overlay
    // to have a video player or iframe on top of the stage as an Image.
    // See BlazyOEmbed::fromMediaOrAny().
    elseif (isset($entity->{$field_name})) {
      if ($reference = $entity->get($field_name)->first()) {
        if ($reference instanceof EntityReferenceItem) {
          $media = $reference->entity;
        }
      }
    }

    return $media instanceof MediaInterface ? $media : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $view_mode, $langcode): array {
    $source     = $media->getSource();
    $definition = $source->getPluginDefinition();
    $source_id  = $source->getPluginId();
    $uri        = '';

    try {
      // GuzzleHttp\Exception\ConnectException: cURL error 6:
      // Could not resolve host: soundcloud.com.
      // @todo recheck and replace if any direct method for URI.
      if ($attr = ($definition['thumbnail_uri_metadata_attribute'] ?? '')) {
        $uri = $source->getMetadata($media, $attr);
      }
    }
    catch (\Exception $e) {
      // No need to be harsh here, likely disconnected internet, we can always
      // display stored thumbnails, if already.
    }

    // Extracts common entity properties.
    $info = CheckItem::entity($media, $langcode);

    // Only eat what we can chew.
    $output = [
      'source'       => $source_id,
      'source_field' => $source->getConfiguration()['source_field'],
      'thumbnail'    => $uri,
      'type'         => $this->getType($source_id),
      'value'        => $source->getSourceFieldValue($media),
      'view_mode'    => $view_mode ?: 'default',
    ] + $info['data'];

    return ['data' => $output, 'entity' => $info['entity']];
  }

  /**
   * {@inheritdoc}
   *
   * @todo recheck other local file-related media sources.
   */
  public function getSource($file): ?string {
    $mime = $file->getMimeType();
    [$type] = explode('/', $mime, 2);
    $source = NULL;

    if ($mime === 'image/svg+xml') {
      $source = 'svg';
    }
    foreach (['audio', 'video'] as $key) {
      if ($type == $key) {
        $source = $key . '_file';
      }
    }
    return $source;
  }

  /**
   * Modifies item attributes for iframes if any.
   */
  public function iframeable(array &$item, array &$settings): bool {
    $iframeable = FALSE;
    $blazies    = $settings['blazies'];
    $original   = $item;
    $uri        = $blazies->get('image.uri');

    // Checks if we have iframes.
    if ($content = $this->manager->renderInIsolation($item)) {
      // Prior to PHP 8.0.0 this method could be called statically, but would
      // issue an E_DEPRECATED error. As of PHP 8.0.0 calling this method
      // statically throws an Error exception.
      // See https://www.php.net/manual/en/domdocument.loadhtml.php.
      $dom = Html::load($content);
      $iframes = $dom->getElementsByTagName('iframe');

      // An image URI must be available to be processed by theme_blazy().
      if ($uri && $iframes->length > 0 && $iframe = $iframes->item(0)) {
        if ($src = $iframe->getAttribute('src')) {
          $iframe_domain = $blazies->get('iframe_domain');
          // For consistency and security, yet ensure to not mess up url.
          if ($blazies->use('oembed')
            && strpos($src, '?') === FALSE
            && strpos($src, '?url=') === FALSE) {
            $src = $this->toEmbedUrl($src, $iframe_domain);
          }

          Internals::toPlayable($blazies, $src, TRUE);

          // All iframes are treated as video, even if image.
          $blazies->set('media.type', 'video');
          $iframeable = TRUE;
        }
      }
      else {
        // @todo recheck if it has media thumbnail URI, and workable.
        $this->disableFeatures($settings, TRUE);
      }

      $item = $original;
    }
    return $iframeable;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$data): MediaInterface {
    $media     = $data['#media'] ?? $data['#entity'];
    $settings  = &$data['#settings'];
    $blazies   = $settings['blazies'];
    $view_mode = $settings['view_mode'] ?? 'default';
    $langcode  = $blazies->get('language.current');
    $result    = $this->getMetadata($media, $view_mode, $langcode);
    $media     = $result['entity'] ?? $media;
    $info      = $result['data'];
    $id        = $info['id'];
    $rid       = $info['rid'];
    $bundle    = $info['bundle'];
    $source    = $info['source'];
    $locals    = ['audio_file', 'video_file'];
    $videos    = ['oembed:video', 'video_embed_field'];
    $medias    = array_merge($locals, $videos);
    $is_local  = in_array($source, $locals);
    $is_media  = in_array($source, $medias);
    $type      = $info['type'] ?? 'image';
    $_type     = str_replace([':'], '_', $type);
    $is_remote = $type == 'video' || in_array($source, $videos);

    // Embed url is not defined here, yet, provides basic media checks.
    $contexts = Cache::mergeContexts(['languages', 'url.site'], $media->getCacheContexts());
    $blazies->set('media', $info)
      ->set('media.instance', $media)
      ->set('cache.metadata.contexts', $contexts, TRUE)
      ->set('cache.metadata.keys', [$id, $rid], TRUE)
      ->set('cache.metadata.max-age', $media->getCacheMaxAge())
      ->set('cache.metadata.tags', $media->getCacheTags(), TRUE)
      // @todo refine the overlaps, playable should accept local files, etc.
      // The clearest so far are iframeable vs. iframe, multimedia, local_video.
      // OK for 2.17 since no real usages except for few.
      // See CheckItem::multimedia() for current usage definitions.
      ->set('is.instagram_api', $source == 'oembed:instagram')
      ->set('is.playable', $is_remote || $is_local)
      ->set('is.multimedia', $is_media)
      ->set('is.local_media', $is_local)
      ->set('is.remote_video', $is_remote)
      ->set('is.remote_unknown', !$is_media)
      ->set('is.' . $_type, TRUE)
      ->set('field.target_bundles.' . $bundle, $bundle, TRUE);

    // @todo remove for is.type:
    $blazies->set('is.local_audio', $source == 'audio_file')
      ->set('is.local_video', $source == 'video_file');

    // Just to be sure in case called anywhere else.
    $data['#media'] = $media;
    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function toEmbedUrl($input, $iframe_domain, array $parameters = []): string {
    $query = [
      'url' => $input,
      'max_width' => 0,
      'max_height' => 0,
      'hash' => $this->iFrameUrlHelper->getHash($input, 0, 0),
      'blazy' => 1,
    ] + $parameters;

    // @todo revisit if any issue with other resource types.
    $url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => $query,
    ]);

    // The top level iframe url relative to the site, or iframe_domain.
    if ($iframe_domain) {
      $url->setOption('base_url', $iframe_domain);
    }

    return Internals::correct($url->toString());
  }

  /**
   * Returns media sources that do not kill the site when disconnected.
   */
  private function getSafeSource($source) {
    return in_array($source, [
      'd500px',
      'flickr',
      'oembed:instagram',
      'pinterest',
      'twitter',
    ]);
  }

  /**
   * The media.type is a legacy 1.x with VEF, not official Media property.
   *
   * Just to simplify usage, or complex application downstream.
   */
  private function getType($source_id): string {
    $images = in_array($source_id, ['image', 'svg']);
    $videos = in_array($source_id, [
      'oembed:video',
      'video_embed_field',
    ]);

    if ($images) {
      $type = 'image';
    }
    elseif ($videos) {
      $type = 'video';
    }
    else {
      $type = $source_id;
    }
    return $type;
  }

  /**
   * Disable fancy features with the unknown land.
   *
   * @todo add an option for thumbnail preview rather than entity view.
   */
  private function disableFeatures(array &$settings, $rendered = TRUE, $link = NULL): void {
    $blazies = $settings['blazies'];
    $blazies->set('use.content', $rendered);

    // @todo recheck, might be dynamic link to iframe like Pinterest:
    // Pinterest is not here.
    if ($link) {
      $settings['media_switch'] = 'content';
      $blazies->set('switch', 'content')
        ->set('media.link', $link)
        ->set('is.lightbox', FALSE);
    }
  }

  /**
   * Modifies item attributes for local audio/video item.
   */
  private function toLocal(array &$item, array &$settings, $file): void {
    $blazies = $settings['blazies'];

    // @todo multiple sources, not crucial for now.
    // This is not an image URI, but file video URI.
    // The poster or file image URI is set via settings.image option instead.
    $blazies->set('media.uri', $file->getFileUri());

    // Do this as $item['#settings'] is not available as file_video variables.
    // @todo re-check, most likely just a single file here.
    foreach ($item['#files'] as &$file) {
      $file['#blazy'] = $this->manager->settings($settings);
    }

    $item['#attributes']->setAttribute('data-b-lazy', TRUE);

    // Disable [data-src] lazy if undata, or richbox is supported.
    if ($blazies->is('undata') || $blazies->is('richbox')) {
      $item['#attributes']->setAttribute('data-b-undata', TRUE);
    }

    $item['#attached']['library'][] = 'blazy/multimedia';
  }

  /**
   * Returns a field item/ content to avoid nested field markups.
   *
   * @param array $field
   *   The source renderable array to remove field markups from for DOM diet.
   *
   * @return array
   *   The array of the media item to be wrapped directly by theme_blazy().
   */
  private function unfield(array &$field): array {
    $item      = $field[0];
    $settings  = &$field['#settings'];
    $blazies   = $settings['blazies'];
    $is_iframe = ($item['#tag'] ?? NULL) == 'iframe';

    if (!isset($item['#attributes'])) {
      $item['#attributes'] = [];
    }

    $attributes = &$item['#attributes'];

    // Update iframe/video dimensions based on configurable image style, if any.
    foreach (['width', 'height'] as $key) {
      if ($dimension = ($blazies->get('image.' . $key))) {
        $attributes[$key] = $dimension;
      }
    }

    // Converts iframes into lazyloaded ones.
    // Iframes: Googledocs, SlideShare. Hardcoded: Spotify.
    // @todo recheck, likely everyone hardly uses iframes #html_tag lately,
    // except core OEmbed formatter, taken care of by just input, not here,
    // unless delegated by other formatters.
    // No longer per D9.5: Soundcloud.
    if ($is_iframe && $src = ($attributes['src'] ?? FALSE)) {
      Internals::toPlayable($blazies, $src, TRUE);

      // All iframes are treated as video, even if image.
      $blazies->set('media.type', 'video');
    }
    // Media with local files: video.
    elseif (isset($item['#files'])
      && $file = ($item['#files'][0]['file'] ?? NULL)) {
      $this->toLocal($item, $settings, $file);
      $blazies->set('use.content', TRUE);
    }
    elseif ($theme = $item['#theme'] ?? NULL) {
      // Resource::TYPE_PHOTO.
      if ($theme == 'image') {
        $blazies->set('media.type', 'image')
          ->set('use.content', FALSE);

        if ($uri = $item['#uri'] ?? NULL) {
          $blazies->set('image.uri', $uri);
        }
      }
      else {
        // Soundcloud, Twitter, etc.
        $this->iframeable($item, $settings);
      }
    }
    else {
      // @todo recheck more media entity tendencies, mostly just #markup.
      // Resource::TYPE_LINK.
      $type = $item['#type'] ?? NULL;
      $link = $type == 'link' && isset($item['#url']) ? $item['#url'] : NULL;

      // Unless required as a thumbnail, render as is.
      $rendered = !$blazies->use('thumbnail');

      // Facebook, and the rest of media entities.
      // At least display thumbnails for empty markups.
      if (isset($item['#markup']) && empty($item['#markup'])) {
        $rendered = FALSE;
      }

      $this->disableFeatures($settings, $rendered, $link);
    }

    // Clone relevant keys since field wrapper is no longer in use.
    foreach (['attached', 'cache', 'third_party_settings'] as $key) {
      if ($data = $field["#$key"] ?? []) {
        $item["#$key"] = $this->manager->merge($data, $item, "#$key");
      }
    }

    // Keep original formatter configurations intact here for custom works.
    // Non-accessible at file_video preprocess, but required by theme_blazy().
    $item['#settings'] = $this->manager->settings($settings);

    return $item;
  }

}
