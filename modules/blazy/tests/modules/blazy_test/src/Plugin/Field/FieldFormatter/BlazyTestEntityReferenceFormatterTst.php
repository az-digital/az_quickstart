<?php

namespace Drupal\blazy_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyEntityReferenceBase;
use Drupal\blazy\internals\Internals;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Blazy Entity Reference' formatter.
 *
 * @FieldFormatter(
 *   id = "blazy_entity_test",
 *   label = @Translation("Blazy Entity Reference Test"),
 *   field_types = {"entity_reference", "file"}
 * )
 */
class BlazyTestEntityReferenceFormatterTst extends BlazyEntityReferenceBase {

  /**
   * {@inheritdoc}
   */
  protected static $fieldType = 'entity';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return static::injectServices($instance, $container, static::$fieldType);
  }

  /**
   * Returns the blazy_test admin service shortcut.
   */
  public function admin() {
    return Internals::service('blazy_test.admin');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return BlazyDefault::extendedSettings()
      + BlazyDefault::gridSettings()
      + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    return $this->commonViewElements($items, $langcode, $entities);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginScopes(): array {
    $admin       = $this->admin();
    $target_type = $this->getFieldSetting('target_type');
    $bundles     = $this->getAvailableBundles();
    $node        = $admin->getFieldOptions($bundles, ['entity_reference'], $target_type, 'node');
    $stages      = $admin->getFieldOptions($bundles, ['image'], $target_type);

    return [
      'namespace'  => 'blazy_test',
      'images'     => $stages,
      'overlays'   => $stages + $node,
      'thumbnails' => $stages,
      'optionsets' => ['default' => 'Default'],
    ] + parent::getPluginScopes();
  }

}
