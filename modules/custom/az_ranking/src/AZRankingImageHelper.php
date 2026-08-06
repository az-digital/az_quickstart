<?php

namespace Drupal\az_ranking;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\media\MediaInterface;

/**
 * Pulls the image file and focal point off a ranking's media entity.
 *
 * Returns plain data, not a render array. Turning that into markup is the
 * az_quickstart:ranking-image component's job.
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
   * Gets the image file URI and focal point from a ranking's media entity.
   *
   * The published ranking and the widget's edit-form preview both reach
   * this through AZRankingComponentBuilder::buildImageComponent(), so the
   * two can't drift apart.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A Drupal media entity object.
   *
   * @return array
   *   An array with these keys:
   *   - 'src': the image's file URI, or an empty string if the media has
   *     no image on it.
   *   - 'focal_x', 'focal_y': the focal point, or NULL if none is set.
   *   - 'cache_tags': the file's cache tags. Attach these to whatever
   *     render array you build from this data, or a cached ranking will
   *     keep showing the old picture after someone replaces the file.
   *     Nothing upstream does it for you: az_ranking stores its media
   *     reference as a plain integer property (see
   *     AZRankingItem::propertyDefinitions()) rather than an entity
   *     reference, so Drupal derives no cache metadata from it.
   */
  public function getImageSourceAndFocalPoint(MediaInterface $media): array {
    $empty = [
      'src' => '',
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
        // If reading the focal point goes wrong, leave focal_x and focal_y
        // NULL and carry on. Rationale: with no focal point the image just
        // stays centered, which is a fine-looking result. Not worth taking
        // the page down over.
      }
    }

    return $result;
  }

}
