<?php

namespace Drupal\ib_dam_media;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\ib_dam\Downloader;
use Drupal\ib_dam_media\Exceptions\MediaTypeMatcherBadMediaTypes;
use Exception;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * Class MediaTypeMatcher.
 *
 * The service to match media types with a given source types,
 * get allowed file extensions.
 *
 * @package Drupal\ib_dam_media
 */
class MediaTypeMatcher {

  protected $entityTypeManager;
  protected $mimeGuesser;
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, MimeTypeGuesserInterface $mime_type_guesser, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('ib_dam_media.settings');
    $this->mimeGuesser = $mime_type_guesser;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a list of supported source types.
   *
   * Used as sort of manager for source types.
   *
   * @return array
   *   Array of supported source types: source type <--> field types, mimes.
   */
  public static function getSupportedSourceTypes() {
    // @todo: refactor this, use source_field_type?
    $types = [
      'image' => [
        'label' => 'Image type',
        'supported_field_types' => ['image'],
        'supported_mimetype_names' => ['image'],
      ],
      'audio' => [
        'label' => 'Audio type',
        'supported_field_types' => ['file'],
        'supported_mimetype_names' => ['audio'],
      ],
      'video' => [
        'label' => 'Video type',
        'supported_field_types' => ['file'],
        'supported_mimetype_names' => ['video'],
      ],
      'file' => [
        'label' => 'File type',
        'supported_field_types' => ['file'],
        'supported_mimetype_names' => ['application', 'text', 'font', 'model'],
      ],
      'embed' => [
        'label' => 'Embed type',
        'supported_field_types' => ['link'],
        'supported_mimetype_names' => TRUE,
      ],
    ];
    return $types;
  }

  /**
   * Match type by either source type or media type.
   *
   * @param string $needle
   *   Type value to search.
   * @param string $type
   *   The key to check.
   *
   * @return null|string
   *   Matched either media_type or source_type value.
   */
  public function matchType($needle, $type = 'source_type') {
    if (!$needle) {
      return NULL;
    }

    $match = NULL;

    foreach ((array) $this->config->get('media_types') as $item) {
      if (isset($item['media_type'])
        && isset($item['source_type'])
        && $item[$type] == $needle
      ) {
        $match = $type == 'source_type'
          ? $item['media_type']
          : $item['source_type'];
        break;
      }
    }

    return $match;
  }

  /**
   * Build a match object for all field types.
   *
   * @return array
   *   Array of settings per field type, containing:
   *   - 'media_type': media type where used field type,
   *   - 'asset_types': extracted asset types from allowed file extensions,
   *   - 'extensions': allowed file types for the field.
   */
  public function getMediaSourceFieldTypes() {
    $field_types = &drupal_static(__METHOD__, []);

    if (!empty($field_types)) {
      return $field_types;
    }

    try {
      $media_bundles = $this->entityTypeManager
        ->getStorage('media_type')
        ->loadMultiple();
    }
    catch (Exception $e) {
      (new MediaTypeMatcherBadMediaTypes($e->getMessage()))
        ->logException()
        ->displayMessage();
      return [];
    }
    /** @var \Drupal\media\Entity\MediaType $bundle */
    foreach ($media_bundles as $bundle_name => $bundle) {
      $discrete_types = [];

      $source_field = $bundle->getSource()
        ->getConfiguration()['source_field'];

      $field  = FieldConfig::loadByName('media', $bundle_name, $source_field);
      $e_list = $field->getSetting('file_extensions');

      if (empty($e_list)) {
        $e_list = '';
      }

      $e_list = array_filter((array) explode(' ', $e_list));

      foreach ($e_list as $ext) {
        $fake_name = sprintf('%s://nothing.%s', \Drupal::config('system.file')->get('default_scheme'), $ext);
        $mimetype = $this->mimeGuesser->guessMimeType($fake_name);
        $discrete_type = Downloader::getSourceTypeFromMime($mimetype);
        $discrete_types[] = $discrete_type;
      }

      if (!$field->isDeleted()) {
        $field_types[$field->getType()][] = [
          'media_type' => $bundle,
          'asset_types' => array_unique($discrete_types),
          'extensions' => $e_list,
        ];
      }
    }
    return $field_types;
  }

  /**
   * Collect all allowed file extensions either for a given media types or all.
   *
   * @param \Drupal\media\Entity\MediaType[] $media_types
   *   List of media types instances to get data.
   * @param bool $load_all
   *   Collect file extensions even if there is empty media types variable.
   *
   * @return array
   *   An array of allowed file extensions.
   */
  public function getAllowedFileExtensions(array $media_types = [], $load_all = TRUE) {
    if (empty($media_types) && $load_all === FALSE) {
      return [];
    }

    $field_types = $this->getMediaSourceFieldTypes();
    $extensions = [];

    foreach ($field_types as $items) {
      foreach ($items as $type) {
        if (!isset($type['extensions'])) {
          continue;
        }
        if (empty($media_types) || in_array($type['media_type']->id(), $media_types)) {
          $extensions = array_merge($extensions, $type['extensions']);
        }
      }
    }
    return $extensions;
  }

  /**
   * Get all media types that could be used for a given source type.
   *
   * @param string $source_type
   *   The source type.
   *
   * @return array
   *   An array of media type id and label.
   */
  public function getSupportedMediaTypes($source_type) {
    $types = static::getSupportedSourceTypes();
    $field_types = $this->getMediaSourceFieldTypes();
    $media_types = [];

    $mime_names = $types[$source_type]['supported_mimetype_names'];
    $supported_field_types = $types[$source_type]['supported_field_types'];

    foreach ($supported_field_types as $field_type) {
      // First we check for field type.
      if (empty($field_types[$field_type])) {
        continue;
      }
      // Next check for supported asset types based on allowed file extensions.
      foreach ($field_types[$field_type] as $settings) {
        if ($mime_names === TRUE
          || array_intersect((array) $mime_names, $settings['asset_types'])
        ) {
          $media_types[$settings['media_type']->id()] = $settings['media_type']->label();
        }
      }
    }
    return $media_types;
  }

}
