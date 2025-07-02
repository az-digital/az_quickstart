<?php

namespace Drupal\ib_dam_media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media source plugin for video embed field.
 *
 * @MediaSource(
 *   id = "ib_dam_embed",
 *   label = @Translation("IB DAM embed media source"),
 *   description = @Translation("Enables IntelligenceBank public link integration with media."),
 *   allowed_field_types = {"link"},
 *   forms = {
 *     "media_library_add" = "\Drupal\ib_dam_media\Form\MediaLibraryIbDamRemoteAssetAddForm",
 *   },
 * )
 */
class IbDamEmbedField extends MediaSourceBase {

  /**
   * The media settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mediaSettings;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   Config field type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManagerInterface $entity_type_manager,
      EntityFieldManagerInterface $entity_field_manager,
      FieldTypePluginManagerInterface $field_type_manager,
      ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->mediaSettings = $config_factory->get('media.settings');
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
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $metadata = [
      'thumbnail_uri' => $this->mediaSettings->get('icon_base_uri')
        . '/' . $this->pluginDefinition['default_thumbnail_filename'],
    ];
    if (isset($media->original_item)) {
      /** @var \Drupal\ib_dam\IbDamResourceModel $original_item */
      $original_item = $media->original_item;
      $metadata += [
        'default_name'   => $original_item->getName(),
        'resource_title' => $original_item->getName(),
        'resource_url'   => $original_item->getUrl(),
        'resource_type'  => $original_item->getType(),
      ];
    }
    else {
      $field = $media->get($this->configuration['source_field'])->first()->getValue();
      $metadata += [
        'default_name'   => $media->getName(),
        'resource_title' => $field['title'],
        'resource_url'   => $field,
        'resource_type'  => $field['options']['attributes']['ib_dam']['asset_type'] ?? FALSE,
      ];
    }

    if (!isset($metadata[$attribute_name]) || empty($metadata[$attribute_name])) {
      return parent::getMetadata($media, $attribute_name);
    }
    return $metadata[$attribute_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'resource_type' => $this->t('Embed resource type.'),
      'resource_title' => $this->t('Embed resource title.'),
      'resource_url' => $this->t('Embed resource url.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Embed Resource Url');
  }

}
