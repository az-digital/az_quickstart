<?php

namespace Drupal\entity_embed\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade path for entity embed buttons.
 *
 * @CKEditor4To5Upgrade(
 *   id = "entity_embed",
 *   cke4_buttons = {
 *   },
 *   cke4_plugin_settings = {
 *   },
 *   cke5_plugin_elements_subset_configuration = {
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class EntityEmbed extends PluginBase implements CKEditor4To5UpgradePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityEmbed constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
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
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    $entity_embed_buttons = $this->entityTypeManager
      ->getStorage('embed_button')
      // @see \Drupal\entity_embed\Plugin\EmbedType\Entity
      ->loadByProperties(['type_id' => 'entity']);
    foreach ($entity_embed_buttons as $entity_embed_button) {
      // Embed buttons have the same ids in CKEditor 4 and 5, so this is
      // essentially a check to confirm the embed button still exists.
      if ($cke4_button === $entity_embed_button->id()) {
        return [$cke4_button];
      }
    }

    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
