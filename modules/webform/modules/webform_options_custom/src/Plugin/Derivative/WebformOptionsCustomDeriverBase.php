<?php

namespace Drupal\webform_options_custom\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base class for webform custom options derivers.
 */
abstract class WebformOptionsCustomDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  use WebformEntityStorageTrait;

  /**
   * The type of custom element (element or entity_reference).
   *
   * @var string
   */
  protected $type;

  /**
   * Constructs new WebformReusableCompositeDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $webform_options_custom_entities = $this->getEntityStorage('webform_options_custom')->loadMultiple();
    foreach ($webform_options_custom_entities as $webform_options_custom_entity) {
      if ($webform_options_custom_entity->get($this->type)) {
        $this->derivatives[$webform_options_custom_entity->id()] = $base_plugin_definition;
        $this->derivatives[$webform_options_custom_entity->id()]['label'] = $webform_options_custom_entity->label() . ($this->type === 'entity_reference' ? ' ' . $this->t('(Entity reference)') : '');
        $this->derivatives[$webform_options_custom_entity->id()]['description'] = $webform_options_custom_entity->get('description');
      }
    }
    return $this->derivatives;
  }

}
