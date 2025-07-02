<?php

namespace Drupal\blazy\Media;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\blazy\Utility\Path;
use Drupal\blazy\internals\Internals;
use Drupal\file\FileInterface;
use Drupal\file\FileRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides file_BLAH for D9.3 - D11+.
 *
 * Blazy 3.x now depends on D9.4, not D9.2, safe to remove deprecated.
 *
 * @see https://www.drupal.org/node/2940031
 */
class BlazyFile implements BlazyFileInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepository
   */
  protected $fileRepository;

  /**
   * The image object.
   *
   * @var \Drupal\Core\Image\ImageInterface|null
   */
  protected $image;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a SVG manager object.
   */
  public function __construct(
    FileSystemInterface $file_system,
    FileRepository $file_repository,
    ImageFactory $image_factory,
    LoggerChannelFactoryInterface $logger,
  ) {
    $this->fileSystem = $file_system;
    $this->fileRepository = $file_repository;
    $this->imageFactory = $image_factory;
    $this->logger = $logger->get('image');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('image.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fileSystem(): FileSystemInterface {
    return $this->fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public function fileRepository(): FileRepository {
    return $this->fileRepository;
  }

  /**
   * {@inheritdoc}
   */
  public function imageFactory(): ImageFactory {
    return $this->imageFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function image($source = NULL, $toolkit_id = NULL): ImageInterface {
    return $this->imageFactory->get($source, $toolkit_id);
  }

  /**
   * {@inheritdoc}
   */
  public function realpath($uri): string {
    return $this->fileSystem->realpath($uri);
  }

  /**
   * Returns TRUE if an external URL.
   */
  public static function isExternal($uri): bool {
    return $uri && UrlHelper::isExternal($uri);
  }

  /**
   * Returns TRUE if a File entity.
   */
  public static function isFile($file): bool {
    return $file instanceof FileInterface;
  }

  /**
   * Determines whether the URI has a valid scheme for file API operations.
   *
   * @param string $uri
   *   The URI to be tested.
   *
   * @return bool
   *   TRUE if the URI is valid.
   */
  public static function isValidUri($uri): bool {
    if (!empty($uri) && $manager = Path::streamWrapperManager()) {
      return $manager->isValidUri($uri);
    }
    return FALSE;
  }

  /**
   * Creates a relative or absolute web-accessible URL string.
   *
   * @param string $uri
   *   The file uri.
   * @param bool $relative
   *   Whether to return an relative or absolute URL.
   *
   * @return string
   *   Returns an absolute web-accessible URL string.
   */
  public static function createUrl($uri, $relative = FALSE): string {
    if ($gen = Path::fileUrlGenerator()) {
      // @todo recheck ::generateAbsoluteString doesn't return web-accessible
      // protocol as expected, required by getimagesize to work correctly.
      return $relative
        ? $gen->generateString($uri)
        : $gen->generateAbsoluteString($uri);
    }
    return '';
  }

  /**
   * Transforms an absolute URL of a local file to a relative URL.
   *
   * Blazy Filter or OEmbed may pass mixed (external) URI upstream.
   *
   * @param string $uri
   *   The file uri.
   * @param object $style
   *   The optional image style instance.
   * @param array $options
   *   The options: default url.
   *
   * @return string
   *   Returns an absolute URL of a local file to a relative one.
   *
   * @see BlazyOEmbed::getThumbnail()
   * @see BlazyFilter::getImageItemFromImageSrc()
   */
  public static function transformRelative($uri, $style = NULL, array $options = []): string {
    $url = $options['url'] ?? '';

    if (empty($uri)) {
      return $url;
    }

    // Returns as is if an external URL: UCG or external OEmbed image URL.
    if (self::isExternal($uri)) {
      return $uri;
    }

    // If valid URI, use image style, or as is, and make it relative path.
    if (self::isValidUri($uri)) {
      $stylable = $style && !self::isSvg($uri);
      $url = $stylable ? $style->buildUrl($uri) : self::createUrl($uri);

      if ($gen = Path::fileUrlGenerator()) {
        $url = $gen->transformRelative($url);
      }
    }

    // If transform failed, returns default URL, or URI as is.
    return $url ?: $uri;
  }

  /**
   * Returns URI from the given image URL, relevant for unmanaged/ UGC files.
   *
   * Converts `/sites/default/files/image.jpg` into `public://image.jpg`.
   *
   * @todo re-check if core has this type of conversion.
   */
  public static function buildUri($url): ?string {
    if (!self::isExternal($url)
      && $normal_path = UrlHelper::parse($url)['path']) {

      // If the request has a base path, remove it from the beginning of the
      // normal path as it should not be included in the URI.
      $base_path = \Drupal::request()->getBasePath();
      if ($base_path && mb_strpos($normal_path, $base_path) === 0) {
        $normal_path = str_replace($base_path, '', $normal_path);
      }

      $scheme = \blazy()->config('default_scheme', 'system.file');

      $active_path = $scheme == 'public'
        ? PublicStream::basePath()
        : Settings::get('file_private_path');

      // Only concerns for the correct URI, not image URL which is already being
      // displayed via SRC attribute. Don't bother language prefixes for IMG.
      if ($active_path && mb_strpos($normal_path, $active_path) !== FALSE) {
        $path = str_replace($active_path, '', $normal_path);
        return self::normalizeUri($path);
      }
    }
    return NULL;
  }

  /**
   * Returns a file object from an URI.
   */
  public static function fromUri($uri, $manager = NULL): ?object {
    return Internals::loadByProperty('uri', $uri, 'file', $manager);
  }

  /**
   * Returns TRUE if an SVG URI.
   */
  public static function isSvg($uri): bool {
    // Some guy uploaded images without extensions, seen at wildlife.
    if ($ext = pathinfo($uri, PATHINFO_EXTENSION)) {
      // Some other guy put CAPITALIZED image extensions for real.
      $ext = strtolower($ext);
      return $ext == 'svg';
    }
    return FALSE;
  }

  /**
   * Normalizes URI for BlazyFilter URLs, etc., hardly formatters.
   */
  public static function normalizeUri($path): string {
    $uri = $path;
    if ($stream = Path::streamWrapperManager()) {
      // The double slash was from buildUri.
      if (substr($path, 0, 2) === '//') {
        $scheme = \blazy()->config('default_scheme', 'system.file');
        $uri = $scheme . ':' . $path;
      }
      $uri = $stream->normalizeUri($uri);
    }
    return $uri;
  }

  /**
   * Returns web-accessible URI if an invalid is given.
   */
  public static function toAccessibleUri($uri): string {
    $abs = $uri;
    // Must be valid URI, or web-accessible url, not: /modules|themes/...
    if (!self::isValidUri($abs) && mb_substr($abs, 0, 1) == '/') {
      if ($request = Path::requestStack()) {
        $abs = $request->getCurrentRequest()->getSchemeAndHttpHost() . $abs;
      }
    }
    return $abs;
  }

  /**
   * Returns URI from image item, fake or valid one, no problem.
   */
  public static function uri($item, array $settings = []): ?string {
    $uri = NULL;
    if ($item && BlazyImage::isValidItem($item)) {
      $file = $item->entity ?? NULL;
      $uri = $item->uri ?? NULL;
      // The ::getFileUri() may point to local video, not image URI.
      $uri = $uri ?: (self::isFile($file) ? $file->getFileUri() : NULL);
    }

    // No file API with unmanaged files here: hard-coded UGC, legacy VEF.
    if (!$uri && $settings) {
      if ($blazies = $settings['blazies'] ?? NULL) {
        $uri = $blazies->get('image.uri');
      }
    }

    return $uri ?: $settings['uri'] ?? NULL;
  }

  /**
   * Returns the File entity from any object, or just settings, if applicable.
   *
   * Should be named entity, but for consistency with BlazyImage:item().
   */
  public static function item($object = NULL, array $settings = [], $uri = NULL): ?object {
    $file = $object;
    Internals::verify($settings);

    // Bail out early if we are given what we want.
    /** @var \Drupal\file\Entity\File $file */
    if (self::isFile($file)) {
      return $file;
    }

    // Fake, or real image item. Might also be VEF.
    /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $object */
    if (BlazyImage::isValidItem($object) && $file = $object->entity ?? NULL) {
      // Ensures not locked here, in case VEF put its VEF, etc.
      if (self::isFile($file)) {
        return $file;
      }
    }

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $object */
    if ($object instanceof EntityReferenceItem) {
      /** @var \Drupal\file\Entity\File $file */
      $file = $object->entity;
    }
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $object */
    elseif ($object instanceof EntityReferenceFieldItemListInterface) {
      // @phpstan Variable $image in PHPDoc tag @ var does not exist.
      /** @var \Drupal\image\Plugin\Field\FieldType\ImageItem $image */
      $image = $object->first();
      if ($image) {
        /** @var \Drupal\file\Entity\File $file */
        $file = $image->entity;
      }
    }

    // BlazyFilter without any entity/ formatters associated with.
    // Or any entities: Node, Paragraphs, User, etc. having settings.image.
    if (!self::isFile($file) && $settings) {
      // Extracts File entity from settings.image, the poster image.
      if ($name = $settings['image'] ?? NULL) {
        // With a mix of image and video, image is not always there.
        $file = self::fromField($file, $name, $settings);
      }

      // BlazyFilter without any entity/ formatters associated with.
      // Or legacy VEF with hard-coded image URL without file API.
      if (!self::isFile($file)) {
        $file = self::fromSettings($settings, $uri);
      }
    }

    return self::isFile($file) ? $file : NULL;
  }

  /**
   * Returns the File entity from a field name, if applicable.
   *
   * Main image can be separate image item from video thumbnail for highres.
   * Fallback to default thumbnail if any, which has no file API. This used to
   * be for non-media File Entity Reference at 1.x, things changed since then.
   * Some core methods during Blazy 1.x are now gone at 2.x.
   * Re-purposed for Paragraphs, Node, etc. which embeds Media or File.
   */
  private static function fromField($entity, $name, array $settings): ?object {
    $file = NULL;

    if (!isset($entity->{$name})) {
      return NULL;
    }

    // @phpstan Variable $field in PHPDoc tag @ var does not exist.
    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field */
    $field = $entity->get($name);
    if ($field && method_exists($field, 'referencedEntities')) {
      // Two designated types: MediaInterface and FileInterface.
      $reference = $field->referencedEntities()[0] ?? NULL;
      // The first is FileInterface.
      if (self::isFile($reference)) {
        $file = $reference;
      }
      else {
        // The last is MediaInterface, but let the dogs out for now.
        $options = [
          'entity' => $reference,
          'source' => $entity,
          'settings' => $settings,
        ];
        if ($image = BlazyImage::fromContent($options, $name)) {
          $file = $image->entity;
        }
      }
    }
    return $file;
  }

  /**
   * Returns the File entity from settings, if applicable, relevant for Filter.
   */
  private static function fromSettings(array $settings, $uri = NULL): ?object {
    $blazies = $settings['blazies'] ?? NULL;
    $uri     = $uri ?: self::uri(NULL, $settings);
    $uuid    = $blazies ? $blazies->get('entity.uuid') : NULL;
    $file    = $uuid ? Internals::loadByUuid($uuid, 'file') : NULL;

    if (!$file && $uri) {
      $file = self::fromUri($uri);
    }
    return $file;
  }

}
