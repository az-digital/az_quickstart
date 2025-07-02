<?php

namespace Drupal\crop\Plugin\Crop\EntityProvider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\EntityProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Media crop integration.
 *
 * @CropEntityProvider(
 *   entity_type = "media",
 *   label = @Translation("Media"),
 *   description = @Translation("Provides crop integration for media.")
 * )
 */
class Media extends EntityProviderBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs media integration plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function uri(EntityInterface $entity) {
    $bundle_entity_type = $entity->getEntityType()->getBundleEntityType();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityBase $entity_type */
    $entity_type = $this->entityTypeManager->getStorage($bundle_entity_type)->load($entity->bundle());
    $image_field = $entity_type->getThirdPartySetting('crop', 'image_field');

    if ($entity->{$image_field}->first()->isEmpty()) {
      return FALSE;
    }

    /** @var \Drupal\file\FileInterface $image */
    $image = $this->entityTypeManager->getStorage('file')->load($entity->{$image_field}->target_id);

    if (strpos($image->getMimeType(), 'image') !== 0) {
      return FALSE;
    }

    return $image->getFileUri();
  }

}
