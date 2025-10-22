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
   * Prepare an image render array with context-aware aspect ratio.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A Drupal media entity object.
   * @param array $context
   *   Optional context array with keys:
   *   - 'ranking_width': The parent paragraph's ranking width setting.
   *   - 'column_span': The selected column span (1-4).
   *   - 'ranking_type': 'standard' or 'image_only'.
   *
   * @return array
   *   An image render array.
   */
  public function generateImageRenderArray(MediaInterface $media) {
    $media_render_array = [];
    $media_attributes = $media->get('field_media_az_image')->getValue();

    if (empty($media_attributes[0]['target_id'])) {
      return [];
    }

    if ($file = $this->entityTypeManager->getStorage('file')->load($media_attributes[0]['target_id'])) {
      // Use single responsive image style for all rankings.
      // JavaScript will handle focal point positioning via object-position.
      $image_style = 'az_ranking_responsive';

      $image = new \stdClass();
      $image->title = NULL;
      $image->alt = $media_attributes[0]['alt'] ?? '';
      $image->entity = $file;
      $image->uri = $file->getFileUri();
      $image->width = NULL;
      $image->height = NULL;

      $media_render_array = [
        '#theme' => 'image_formatter',
        '#item' => $image,
        '#image_style' => $image_style,
        '#item_attributes' => [
          'class' => ['ranking-img'],
        ],
      ];
      // Add focal point data attributes for JavaScript to calculate
      // object-position dynamically based on container size.
      if ($media instanceof \Drupal\Core\Entity\FieldableEntityInterface) {
        try {
          if ($media->hasField('field_focal_point_x') && $media->hasField('field_focal_point_y')) {
            if (!$media->get('field_focal_point_x')->isEmpty() && !$media->get('field_focal_point_y')->isEmpty()) {
              $focal_x = (float) $media->get('field_focal_point_x')->value;
              $focal_y = (float) $media->get('field_focal_point_y')->value;

              // Get original image dimensions for JavaScript calculations.
              // When image styles scale the image, naturalWidth/Height in JS
              // will be the scaled dimensions, but focal points are relative
              // to the original image dimensions.
              $image_factory = \Drupal::service('image.factory');
              $original_image = $image_factory->get($file->getFileUri());
              $original_width = $original_image->getWidth();
              $original_height = $original_image->getHeight();

              // Store focal point as decimal values (0-1) for JavaScript,
              // along with original image dimensions.
              $media_render_array['#item_attributes'] += [
                'data-focal-x' => $focal_x,
                'data-focal-y' => $focal_y,
                'data-original-width' => $original_width,
                'data-original-height' => $original_height,
              ];
            }
          }
        }
        catch (\Throwable $e) {
          // Defensive: do not break rendering if fields are not present.
        }
      }
      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $this->renderer->addCacheableDependency($media_render_array, $file);
    }
    return $media_render_array;
  }
}
