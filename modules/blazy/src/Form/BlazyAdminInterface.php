<?php

namespace Drupal\blazy\Form;

use Drupal\blazy\BlazySettings;

/**
 * Defines re-usable services and functions for blazy plugins.
 */
interface BlazyAdminInterface extends BlazyAdminInteropInterface {

  /**
   * Returns the entity display repository.
   */
  public function getEntityDisplayRepository();

  /**
   * Returns the typed config.
   */
  public function getTypedConfig();

  /**
   * Returns the blazy manager.
   */
  public function blazyManager();

  /**
   * Returns simple form elements common for Views field, EB widget, formatters.
   */
  public function baseForm(array &$definition): array;

  /**
   * Returns time in interval for select options.
   */
  public function getCacheOptions(): array;

  /**
   * Returns available lightbox captions for select options.
   */
  public function getLightboxCaptionOptions(): array;

  /**
   * Returns available entities for select options.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The entity types
   */
  public function getEntityAsOptions($entity_type): array;

  /**
   * Returns available optionsets for select options.
   *
   * Might be removed, duplicate for self::getEntityAsOptions() for easy words.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   The entity types
   */
  public function getOptionsetOptions($entity_type): array;

  /**
   * Returns available view modes for select options.
   *
   * @param string $target_type
   *   The target entity type.
   *
   * @return array
   *   The target entity types
   */
  public function getViewModeOptions($target_type): array;

  /**
   * Returns Responsive image for select options.
   *
   * @return array
   *   The responsive images as options.
   */
  public function getResponsiveImageOptions(): array;

  /**
   * Return the field formatter settings summary.
   *
   * @param array $definition
   *   The setting definition.
   *
   * @return array
   *   The settings summary.
   */
  public function getSettingsSummary(array $definition): array;

  /**
   * Returns available fields for select options.
   *
   * @param array $target_bundles
   *   The optional target bundles, might be empty from View UI.
   * @param array $allowed_field_types
   *   The optional field types to query for.
   * @param string $entity_type
   *   The optional entity type.
   * @param string $target_type
   *   The optional target type.
   *
   * @return array
   *   The available fields as options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
  ): array;

  /**
   * Returns common form item title or header classes.
   *
   * @param array $options
   *   The optional additional classes.
   * @param bool $flatten
   *   Whether to flatten the array.
   *
   * @return string|array
   *   The title classes.
   */
  public function getTitleClasses(array $options = [], $flatten = FALSE);

  /**
   * Returns common tooltip classes, normally when bottom position is needed.
   *
   * @param array $options
   *   The optional additional classes.
   * @param bool $flatten
   *   Whether to flatten the array.
   *
   * @return string|array
   *   The tooltip classes.
   */
  public function getTooltipClasses(array $options = [], $flatten = FALSE);

  /**
   * Modifies the grid only form elements.
   */
  public function gridOnlyForm(array &$form, array &$definition): void;

  /**
   * Returns TRUE if admin_css option enabled, else FALSE.
   *
   * @return bool
   *   TRUE if admin CSS is enabled.
   */
  public function isAdminCss(): bool;

  /**
   * Returns TRUE if a Layout Builder admin page.
   *
   * @return bool
   *   TRUE if Layout Builder admin page.
   */
  public function isAdminLb(): bool;

  /**
   * Provides horizontal tabs menu for nested details elements.
   */
  public function tabify(array &$form, $form_id, $region): void;

  /**
   * Provides compact description due to small estates in modal.
   */
  public function themeDescription(array &$form, array $parents = []): void;

  /**
   * Returns escaped options.
   *
   * @return array
   *   The escaped options.
   */
  public function toOptions(array $data): array;

  /**
   * Verify the plugin scopes is initialized downstream.
   *
   * @param array $definition
   *   The setting definition.
   *
   * @return \Drupal\blazy\BlazySettings
   *   The BlazySettings object.
   */
  public function toScopes(array &$definition): BlazySettings;

  /**
   * Returns native grid description.
   */
  public function nativeGridDescription();

  /**
   * Returns base descriptions.
   */
  public function baseDescriptions(): array;

  /**
   * Returns grid descriptions.
   */
  public function gridDescriptions(): array;

  /**
   * Returns grid header description.
   */
  public function gridHeaderDescription();

  /**
   * Returns opening descriptions.
   */
  public function openingDescriptions(): array;

  /**
   * Returns SVG description, from SVG image field to support it in Blazy.
   */
  public function svgDescriptions(): array;

  /**
   * Returns closing form descriptions.
   */
  public function closingDescriptions(): array;

}
