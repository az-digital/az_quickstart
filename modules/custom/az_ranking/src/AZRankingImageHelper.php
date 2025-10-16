<?php

namespace Drupal\az_ranking;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\MediaInterface;

/**
 * Class AZRankingImageHelper generates image render arrays for ranking images.
 */
class AZRankingImageHelper {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new AZRankingImageHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Prepare an image render array.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A Drupal media entity object.
   *
   * @return string[]
   *   An image render array.
   */
  public function generateImageRenderArray(MediaInterface $media) {
    $media_render_array = [];
    $media_attributes = $media->get('field_media_az_image')->getValue();

    if (empty($media_attributes[0]['target_id'])) {
      return [];
    }

    if ($file = $this->entityTypeManager->getStorage('file')->load($media_attributes[0]['target_id'])) {
      $image = new \stdClass();
      $image->title = NULL;
      $image->alt = $media_attributes[0]['alt'] ?? '';
      $image->entity = $file;
      $image->uri = $file->getFileUri();
      $image->width = NULL;
      $image->height = NULL;

      // @todo replace with responsive_image_formatter (?),
      // add image style(s), add cache tags, add image classes(?).
      $media_render_array = [
        '#theme' => 'image_formatter',
        '#item' => $image,
        '#image_style' => 'az_ranking_image',
        // Support images smaller than ranking width, eg. full width rankings.
        '#item_attributes' => [
          'class' => ['ranking-img shadow'],
        ],
      ];
      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $this->renderer->addCacheableDependency($media_render_array, $file);
    }
    return $media_render_array;
  }

}
