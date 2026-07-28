<?php

namespace Drupal\az_ranking;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
   * Constructs a new AZRankingImageHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a plain file URI, alt text, and focal point data for ranking-image.
   *
   * Used for both the published az_quickstart:ranking-image render and the
   * widget's own live edit-form preview, via AZRankingComponentBuilder::
   * buildImageComponent() (shared by AZRankingDefaultFormatter and
   * AZRankingWidget::rebuildRankingPreview()) — both render through the
   * same SDC, so the two can't drift apart.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A Drupal media entity object.
   *
   * @return array
   *   An array with 'src' and 'alt' (empty strings if the media has no
   *   image), plus 'focal_x' and 'focal_y' (both NULL if the media has no
   *   focal point set), plus 'cache_tags' - the file entity's own cache
   *   tags, which the caller MUST attach to whatever render array it builds
   *   from this data. az_ranking stores its media reference as a plain
   *   integer property (see AZRankingItem::propertyDefinitions()), not an
   *   entity reference, so Drupal derives no cache metadata for it
   *   automatically - nothing else in the render pipeline will invalidate a
   *   cached ranking when the underlying file is replaced.
   */
  public function getImageSourceAltAndFocalPoint(MediaInterface $media): array {
    $empty = [
      'src' => '',
      'alt' => '',
      'focal_x' => NULL,
      'focal_y' => NULL,
      'cache_tags' => [],
    ];

    $media_attributes = $media->get('field_media_az_image')->getValue();
    if (empty($media_attributes[0]['target_id'])) {
      return $empty;
    }

    $file = $this->entityTypeManager->getStorage('file')->load($media_attributes[0]['target_id']);
    if (!$file) {
      return $empty;
    }

    $result = $empty;
    $result['src'] = $file->getFileUri();
    $result['alt'] = $media_attributes[0]['alt'] ?? '';
    $result['cache_tags'] = $file->getCacheTags();

    if ($media instanceof FieldableEntityInterface) {
      try {
        if ($media->hasField('field_focal_point_x') && $media->hasField('field_focal_point_y')) {
          if (!$media->get('field_focal_point_x')->isEmpty() && !$media->get('field_focal_point_y')->isEmpty()) {
            $result['focal_x'] = (float) $media->get('field_focal_point_x')->value;
            $result['focal_y'] = (float) $media->get('field_focal_point_y')->value;
          }
        }
      }
      catch (\Throwable $e) {
        // Defensive: do not break rendering if fields are not present.
      }
    }

    return $result;
  }

}
