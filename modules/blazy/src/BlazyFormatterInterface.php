<?php

namespace Drupal\blazy;

/**
 * Defines common field formatter-related methods: Blazy, Slick.
 */
interface BlazyFormatterInterface extends BlazyManagerInterface {

  /**
   * Modifies the field formatter base settings inherited by child elements.
   *
   * @param array $settings
   *   The array containing: field-related settings.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   */
  public function fieldSettings(array &$settings, $items): void;

  /**
   * Modifies the field formatter prepared settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   */
  public function buildSettings(array &$build, $items);

  /**
   * Modifies the field formatter minimal settings inherited by child elements.
   *
   * @param array $settings
   *   The array containing: grid settings, in the least.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   */
  public function minimalSettings(array &$settings, $items): void;

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function preBuildElements(array &$build, $items, array $entities = []);

  /**
   * Modifies the field formatter settings inherited by child elements.
   *
   * This method should NOT be used by sub-modules to allow
   * hook_blazy_settings_alter once for the entire ecosystem rather than each
   * hook_alter for every modules, except for few modifications not affecting
   * the hook_alter.
   *
   * @param array $build
   *   The array containing: settings, or potential optionset for extensions.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function preElements(array &$build, $items, array $entities = []): void;

  /**
   * Modifies the field formatter settings not inherited by child elements.
   *
   * @param array $build
   *   The array containing: items, settings, or a potential optionset.
   * @param object $items
   *   The Drupal\Core\Field\FieldItemListInterface items.
   * @param array $entities
   *   The optional entities array, not available for non-entities: text, image.
   */
  public function postBuildElements(array &$build, $items, array $entities = []);

}
