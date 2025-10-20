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
   *   - 'ranking_width': The parent paragraph's ranking width setting
   *   - 'column_span': The selected column span (1-4)
   *   - 'ranking_type': 'standard' or 'image_only'
   *
   * @return array
   *   An image render array.
   */
  public function generateImageRenderArray(MediaInterface $media, array $context = []) {
    $media_render_array = [];
    $media_attributes = $media->get('field_media_az_image')->getValue();

    if (empty($media_attributes[0]['target_id'])) {
      return [];
    }

    if ($file = $this->entityTypeManager->getStorage('file')->load($media_attributes[0]['target_id'])) {
      // Determine the appropriate image style based on context.
      $ranking_width = $context['ranking_width'] ?? 'col-lg-3';
      $column_span = $context['column_span'] ?? 1;
      $ranking_type = $context['ranking_type'] ?? 'standard';
      
      $image_style = $this->getImageStyleForContext($ranking_width, $column_span, $ranking_type);
      
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
        // Support images smaller than ranking width, eg. full width rankings.
        '#item_attributes' => [
          'class' => ['ranking-img'],
        ],
      ];
      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $this->renderer->addCacheableDependency($media_render_array, $file);
    }
    return $media_render_array;
  }

  /**
   * Get the appropriate image style based on ranking context.
   *
   * @param string $ranking_width
   *   The ranking width class (e.g., 'col-lg-3').
   * @param int $column_span
   *   The column span (1-4).
   * @param string $ranking_type
   *   The ranking type ('standard' or 'image_only').
   *
   * @return string
   *   The image style machine name.
   */
  protected function getImageStyleForContext($ranking_width, $column_span, $ranking_type) {
    // Standard rankings don't have images, so return NULL.
    if ($ranking_type !== 'image_only') {
      return NULL;
    }

    // Map from aspect ratios to focal point-based image styles.
    // Based on AZRankingWidget::getAspectRatioData().
    $style_map = [
      'col-lg-12' => [
        'default' => 'az_ranking_5_1',  // 5:1 for any span
      ],
      'col-lg-6' => [
        '1' => 'az_ranking_2_45',  // 2.45:1
        'default' => 'az_ranking_5_1',  // 2+ columns = 5:1
      ],
      'col-lg-4' => [
        '1' => 'az_ranking_1_6',   // 1.6:1
        '2' => 'az_ranking_3_3',   // 3.3:1
        'default' => 'az_ranking_5_1',  // 3+ columns = 5:1
      ],
      'col-lg-3' => [
        '1' => 'az_ranking_1_2',   // 1.2:1
        '2' => 'az_ranking_2_45',  // 2.45:1
        '3' => 'az_ranking_3_8',   // 3.8:1
        '4' => 'az_ranking_5_1',   // 5:1
      ],
    ];

    // Get the width config or fall back to col-lg-3.
    $width_config = $style_map[$ranking_width] ?? $style_map['col-lg-3'];
    
    // Get the specific style for this column span.
    $column_key = (string) $column_span;
    
    // Return specific style or default for this width.
    return $width_config[$column_key] ?? $width_config['default'] ?? 'az_ranking_1_2';
  }

}
