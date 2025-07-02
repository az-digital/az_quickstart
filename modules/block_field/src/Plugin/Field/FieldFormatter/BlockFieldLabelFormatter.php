<?php

namespace Drupal\block_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'block_field_label' formatter.
 *
 * @FieldFormatter(
 *   id = "block_field_label",
 *   label = @Translation("Block field label"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockFieldLabelFormatter extends FormatterBase {

    /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['label'], $configuration['view_mode'], $configuration['third_party_settings'], $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();
      // Make sure the block exists and is accessible.
      if (!$block_instance) {
        continue;
      }

      $access = $block_instance->access($this->currentUser->getAccount(), TRUE);
      CacheableMetadata::createFromRenderArray($elements)
        ->addCacheableDependency($access)
        ->applyTo($elements);
      if (!$access->isAllowed()) {
        continue;
      }

      $elements[$delta] = [
        '#markup' => $block_instance->label(),
      ];

      CacheableMetadata::createFromRenderArray($elements[$delta])
        ->addCacheableDependency($block_instance)
        ->applyTo($elements[$delta]);
    }
    return $elements;
  }

}
