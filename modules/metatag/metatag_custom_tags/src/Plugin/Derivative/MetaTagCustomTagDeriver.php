<?php

namespace Drupal\metatag_custom_tags\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a new metatag_custom_tags tag plugin for custom tags.
 */
class MetaTagCustomTagDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new MetaTagCustomTagDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get a list of all metatag custom tags.
    $config_entity = $this->entityTypeManager->getStorage('metatag_custom_tag');
    $metatag_custom_tags = $config_entity->loadMultiple() ?? [];
    // Now we loop over them and declare the derivatives.
    foreach ($metatag_custom_tags as $id => $metatag_custom_tag) {
      // The base definition includes the annotations defined in the plugin.
      $derivative = $base_plugin_definition;

      // Here we fill in any missing keys on the MetatagTag annotation.
      $derivative['weight']++;
      $derivative['id'] = $id;
      $derivative['name'] = $id;
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $derivative['label'] = $this->t($metatag_custom_tag->get('label'));
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $derivative['description'] = $this->t($metatag_custom_tag->get('description'));
      $derivative['htmlElement'] = $metatag_custom_tag->get('htmlElement');
      $derivative['htmlNameAttribute'] = $metatag_custom_tag->get('htmlNameAttribute');
      $derivative['htmlValueAttribute'] = $metatag_custom_tag->get('htmlValueAttribute');

      // Reference derivatives based on their UUID instead of the record ID.
      $this->derivatives[$derivative['id']] = $derivative;
    }

    return $this->derivatives;
  }

}
