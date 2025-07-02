<?php

declare(strict_types=1);

namespace Drupal\embed\Deriver;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * CKEditor5 plugin deriver for embed buttons.
 */
class EmbedCKEditor5PluginDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DrupalEntityDeriver object.
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
    assert($base_plugin_definition instanceof CKEditor5PluginDefinition);

    /** @var \Drupal\embed\EmbedButtonInterface $embed_button */
    foreach ($this->entityTypeManager->getStorage('embed_button')->loadByProperties(['type' => $base_plugin_definition->id()]) as $embed_button) {
      $embed_button_id = $embed_button->id();
      $embed_button_label = Html::escape($embed_button->label());
      $plugin_id = "{$base_plugin_definition->id()}_{$embed_button_id}";
      $definition = $base_plugin_definition->toArray();
      $definition['id'] .= $embed_button_id;
      $definition['drupal']['label'] = $base_plugin_definition->label() . ' - ' . $embed_button_label;
      $definition['drupal']['toolbar_items'] = [
        $embed_button_id => [
          'label' => $embed_button_label,
        ],
      ];
      foreach ($definition['drupal']['elements'] as $element) {
        if (str_contains('data-embed-button', $element)) {
          $definition['drupal']['elements'][] = str_replace('data-embed-button', "data-embed-button=\"{$embed_button_id}\"", $element);
        }
      }
      $this->derivatives[$plugin_id] = new CKEditor5PluginDefinition($definition);
    }

    return $this->derivatives;
  }

}
