<?php

namespace Drupal\az_canvas_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Converts a media entity's focal point into a Canvas focal point prop.
 *
 * Quickstart media store focal points as 0-1 fractions in
 * field_focal_point_x/y. Canvas Focal Point Widget's props use whole 0-100
 * percentages. Returns NULL if the media or its focal point is missing.
 *
 * Available configuration keys:
 * - axis: Either 'x' or 'y'.
 *
 * @code
 * inputs/image_focal_x:
 *   plugin: az_media_focal_point
 *   source: media
 *   axis: x
 * @endcode
 */
#[MigrateProcess('az_media_focal_point')]
class MediaFocalPoint extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!in_array($configuration['axis'] ?? NULL, ['x', 'y'], TRUE)) {
      throw new MigrateException('The "axis" configuration must be "x" or "y".');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }
    $media = $this->entityTypeManager->getStorage('media')->load($value);
    $field = 'field_focal_point_' . $this->configuration['axis'];
    if (!$media instanceof FieldableEntityInterface || !$media->hasField($field) || $media->get($field)->isEmpty()) {
      return NULL;
    }
    // Rounding avoids float artifacts (0.29 * 100 is 28.999...) and loses
    // nothing, as the media fields store 2 decimal places.
    return (int) round((float) $media->get($field)->value * 100);
  }

}
