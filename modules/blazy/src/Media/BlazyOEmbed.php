<?php

namespace Drupal\blazy\Media;

use Drupal\blazy\Blazy;
use Drupal\blazy\internals\Internals;
use Drupal\media\MediaInterface;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides OEmbed integration.
 */
class BlazyOEmbed implements BlazyOEmbedInterface {

  /**
   * Core Media oEmbed url resolver.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * Core Media oEmbed resource fetcher.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\Media\BlazyMediaInterface
   */
  protected $blazyMedia;

  /**
   * The Media oEmbed Resource.
   *
   * @var \Drupal\media\OEmbed\Resource
   */
  protected $resource;

  /**
   * The Provider and Resource cache.
   *
   * @var array
   */
  protected $providerAndResource = [];

  /**
   * The thumbnail cache.
   *
   * @var array
   */
  protected $thumbnail = [];

  /**
   * Constructs a Blazy oEmbed object.
   */
  public function __construct(
    BlazyMediaInterface $blazy_media,
    ResourceFetcherInterface $resource_fetcher,
    UrlResolverInterface $url_resolver,
  ) {
    $this->blazyMedia = $blazy_media;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->blazyManager = $blazy_media->manager();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.media'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceFetcher() {
    return $this->resourceFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrlResolver() {
    return $this->urlResolver;
  }

  /**
   * {@inheritdoc}
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  public function blazyMedia() {
    return $this->blazyMedia;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider($input): ?object {
    try {
      return $this->urlResolver->getProviderByUrl($input);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResource($input): ?object {
    try {
      $url = $this->urlResolver->getResourceUrl($input, 0, 0);
      return $this->resourceFetcher->fetchResource($url);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array &$build): void {
    $access   = $build['#access'] ?? FALSE;
    $entity   = $build['#entity'] ?? NULL;
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $valid    = $entity instanceof MediaInterface;
    $stage    = $settings['image'] ?? NULL;
    $stage    = $blazies->get('field.formatter.image', $stage);
    $media    = $valid ? $entity : NULL;

    // Checks for access.
    if (!$access && $denied = $this->blazyManager->denied($entity)) {
      $build['content'][] = $denied;
      return;
    }

    // Two designated types of $stage: MediaInterface and FileInterface.
    // Since 2.10, Main stage is usable as the main display of a Paragraphs,
    // only if the stage is a Media entity and Overlay is left empty. Basically
    // render the Media and replace its parent $entity. This way if it is a
    // video, Media switch will kick in as a Media player or simply an iframe.
    // Old behavior is intact if Overlay is provided as previously designed.
    // Before 2.10, the stage was always made an Image, and required Overlay
    // to have a video player or iframe on top of the stage as an Image.
    if (!$valid && $entity && $stage && empty($settings['overlay'])) {
      if ($object = $this->blazyMedia->fromField($entity, $stage)) {
        $media = $object;
        $valid = TRUE;
      }
    }

    // Required early by BlazyImage::fromAny() below to get media metadata.
    if ($valid) {
      $build['#media'] = $media;
      // Prepare Media needed settings, extract Media thumbnail, except type.
      $media = $this->blazyMedia->prepare($build);

      // Overrides media with the translated version.
      $build['#media'] = $media;
    }

    // Provides image url earlier for file_video at ::fromMedia to have posters.
    if (!BlazyImage::isValidItem($build)) {
      $entity = $valid ? $media : $entity;
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $entity */
      if ($item = BlazyImage::fromAny($entity, $settings)) {
        $build['#item'] = $item;
      }
    }

    // BlazyFilter/ VEF without file upload [data-entity-uuid], nor File API.
    // Soundcloud, etc.
    if (!BlazyImage::isValidItem($build)) {
      $build['#item'] = $this->getThumbnail($settings);
    }

    // If we have a valid image item, fake or real, no biggies.
    if (BlazyImage::isValidItem($build)) {
      // Marks a hires if valid and so configured, normally field_media_image.
      $blazies->set('is.hires', !empty($stage));

      // Extract ImageItem info so to be consumed by SVG attributes.
      if ($item = $this->blazyManager->toHashtag($build, 'item', NULL)) {
        if ($data = BlazyImage::toArray($item)) {
          $blazies->set('image', $data, TRUE)
            // @todo remove this pingpong at 3.x:
            ->set('image.item', $item);
        }
      }
    }

    /** @var \Drupal\media\Entity\Media $entity */
    if ($valid) {
      $this->fromMedia($build);
    }
    else {
      // Attempts to get image data directly from oEmbed resource.
      // Called by BlazyFilter or deprecated VEF, run after data populated.
      $vef = $blazies->get('media.source') == 'video_embed_field';
      if ($vef || !$entity || !$blazies->get('media.embed_url')) {
        $this->toEmbed($settings);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkInputUrl(array &$settings, $input): ?string {
    $blazies = $settings['blazies'];
    $input = Blazy::sanitizeInputUrl($input);
    $blazies->set('media.input_url', $input);
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail(array &$settings, $fallback = TRUE): ?object {
    $blazies = $settings['blazies'];
    $input   = $blazies->get('media.input_url', $settings['input_url'] ?? NULL);
    $item    = NULL;

    if (!$input) {
      return $item;
    }

    $id = md5($input);
    if (!isset($this->thumbnail[$id])) {
      // Might be NULL for BlazyFilter, VEF, etc., re-check.
      $this->checkProviderAndResource($input, $blazies);

      // Similar to extracting image data from ImageFactory source. Basically,
      // anything from resource is fallback, except for type.
      // Respect hard-coded width and height since no UI for all these here.
      $values = $blazies->get('media.resource', []);
      $uri    = $blazies->get('image.uri', $settings['uri'] ?? NULL);
      $uri    = $uri ?: $values['uri'] ?? NULL;
      $height = $blazies->get('image.height') ?: $values['height'] ?? NULL;
      $width  = $blazies->get('image.width') ?: $values['width'] ?? NULL;
      $label  = $blazies->get('media.label') ?: $values['title'] ?? NULL;
      $title  = $blazies->get('image.title') ?: $label;
      $type   = $blazies->get('media.type', $settings['type'] ?? NULL);
      $type   = $values['type'] ?? $type;
      $type   = $type == 'photo' ? 'image' : $type;

      // Redefines for sure so that VEF has image title.
      $blazies->set('media.input_url', $input)
        ->set('media.label', $title)
        ->set('media.type', $type);

      // VEF has just URI, the rest are fetched from resource.
      // Also Soundcloud here.
      if ($uri && $fallback) {
        $dims = [
          'width'  => $width,
          'height' => $height,
        ];
        $data = [
          'uri'   => $uri,
          'alt'   => $title,
          'title' => $label ?: $title,
        ] + $dims;

        // We are here from BlazyFilter, VEF, or where no File API available.
        $blazies->set('image', $data, TRUE);
        $data = $blazies->get('image');
        $item = $blazies->toImage($data);

        // @todo move it out of here:
        $blazies->set('image.item', $item)
          ->set('image.original', $dims, TRUE);
      }

      $this->thumbnail[$id] = $item;
    }

    return $this->thumbnail[$id];
  }

  /**
   * {@inheritdoc}
   */
  public function toEmbedUrl($blazies, $input, array $params = []): string {
    $iframe_domain = $blazies->get('iframe_domain');

    return $this->blazyMedia->toEmbedUrl($input, $iframe_domain, $params);
  }

  /**
   * Checks for the provider to determine oembed, or not.
   */
  private function checkProvider($input, $blazies): void {
    if (!$blazies->was('provider')) {
      $name = $blazies->get('media.provider');
      $use_oembed = FALSE;

      // Might be NULL for BlazyFilter, VEF, etc., re-define.
      if ($provider = $this->getProvider($input)) {
        $name = strtolower($provider->getName());
        $use_oembed = TRUE;
      }

      // Unless disabled via UI even if oEmbed provider exists, specific for VEF
      // to avoid failing expectations with some providers.
      if ($blazies->is('vef') && !$blazies->ui('use_oembed', FALSE)) {
        $use_oembed = FALSE;
      }

      $blazies->set('use.oembed', $use_oembed);
      if ($name) {
        $ratio = !Internals::irrational($name);
        $blazies->set('is.' . $name, TRUE)
          ->set('media.ratio', $ratio)
          ->set('media.provider', $name)
          ->set('was.provider', TRUE);
      }
    }
  }

  /**
   * Checks for the provider resources.
   */
  private function checkResource($input, $blazies): void {
    if (!$blazies->get('media.resource.input')
      && $resource = $this->fromResource($input)) {
      $blazies->set('media.resource', $resource, TRUE)
        ->set('was.resource', TRUE);
    }
  }

  /**
   * Checks for the provider and its resources, to determine oembed, or not.
   *
   * @param string $input
   *   The media input url.
   * @param object $blazies
   *   The blazies object to check and store the provider and its resources.
   */
  private function checkProviderAndResource($input, $blazies): void {
    $id = md5($input);
    if (!isset($this->providerAndResource[$id])) {
      if (!$blazies->was('provider')) {
        $this->checkProvider($input, $blazies);
      }

      if (!$blazies->was('resource')) {
        $this->checkResource($input, $blazies);
      }

      $this->providerAndResource[$id] = $id;
    }
  }

  /**
   * Modifies data to provide Media item thumbnail, embed URL, or rich content.
   *
   * @param array $build
   *   The modified array containing: settings, and candidate video thumbnail.
   */
  private function fromMedia(array &$build): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];
    $input    = $blazies->get('media.value');
    $source   = $blazies->get('media.source');

    // Local video/ audio file were fully supported since 2.17.
    // @todo support other media sources: Resource::TYPE_PHOTO,
    // Resource::TYPE_RICH, etc.
    switch ($source) {
      case 'oembed':
      case 'oembed:video':
      case 'video_embed_field':
        // @todo re-check:
        // case 'oembed:instagram':
        // case 'twitter':
        // case 'facebook':
        // case 'pinterest':
        // Input url != embed url. For Youtube, /watch != /embed.
        if ($input) {
          $blazies->set('media.input_url', $input);
          $this->toEmbed($settings);
        }
        break;

      case 'image':
      case 'svg':
        // Let's keep it for switch purposes.
        $blazies->set('media.type', 'image')
          ->set('media.provider', 'local')
          ->set('media.input_url', NULL)
          ->set('media.embed_url', NULL);
        break;

      default:
        if ($input) {
          // Local audio/video has numeric value, skip.
          if (is_numeric($input)) {
            $blazies->set('media.provider', 'local')
              ->set('media.input_url', NULL)
              ->set('media.embed_url', NULL)
              ->set('lazy.html', FALSE);
          }
          else {
            $blazies->set('media.input_url', $input);
            $this->toEmbed($settings);
          }
        }

        // Supports other Media entities: Facebook, Instagram, local media, etc.
        // Attempts to enter the unknown here fearlessly.
        if ($result = $this->blazyMedia->view($build)) {
          // Update with the processed settings.
          $newbies  = $build['#settings'];
          $settings = $this->blazyManager->mergeSettings('blazies', $settings, $newbies);
          $blazies  = $settings['blazies'];

          $build['#settings'] = $settings;

          // Iframe, like image, can be handled by theme_blazy(). The rest
          // that Blazy doesn't understand should be respected as is as content.
          if ($blazies->use('content')) {
            $build['content'][] = $result;
          }
        }
        break;
    }
  }

  /**
   * Returns image related info from a resource.
   *
   * @param string $input
   *   The input url.
   *
   * @return array
   *   The media data from a resource.
   */
  private function fromResource($input): array {
    $output = [];

    // Failsafe, BlazyFilter/ VEF without file upload [data-entity-uuid].
    // Iframe URL may be valid, but not stored as a Media entity.
    if ($input && $resource = $this->getResource($input)) {
      if ($resource instanceof Resource) {
        $output['input'] = $input;
        $output['type']  = $resource->getType();
        $output['title'] = $resource->getTitle();

        // VEF has valid URI, other hard-coded unmanaged files might not.
        // All we have here is external images. URI validity is not crucial.
        // Be sure internet is connected, or you got headaches.
        if ($uri = $resource->getThumbnailUrl()) {
          $output['uri'] = $uri->getUri();
        }

        if ($url = $resource->getUrl()) {
          $output['url'] = $url->toString();
        }

        if ($html = $resource->getHtml()) {
          $output['html'] = $html;
        }

        $output['width']  = $resource->getThumbnailWidth() ?: $resource->getWidth();
        $output['height'] = $resource->getThumbnailHeight() ?: $resource->getHeight();
      }
    }

    return $output;
  }

  /**
   * Converts input URL into embed URL, run after ::prepare() populated.
   *
   * @param array $settings
   *   The settings array being modified.
   */
  private function toEmbed(array &$settings): void {
    $blazies = $settings['blazies'];
    $input   = $blazies->get('media.input_url');
    $switch  = $settings['media_switch'] ?? NULL;

    if (empty($input)) {
      return;
    }

    $input  = $this->checkInputUrl($settings, $input);
    $params = $switch ? ['autoplay' => 1] : [];

    $this->checkProviderAndResource($input, $blazies);

    // Listen to VEF, or others which might want to set this.
    $embed_url = $blazies->get('media.embed_url');

    // W/o internet, display an (empty) iframe, or a thumbnail.
    if ($blazies->use('oembed') || !$embed_url) {
      $embed_url = $this->toEmbedUrl($blazies, $input, $params);
    }

    // Sets the correct value.
    $embed_url = Internals::correct($embed_url);
    $blazies->set('media.embed_url', $embed_url)
      ->set('media.escaped', TRUE);
  }

}
