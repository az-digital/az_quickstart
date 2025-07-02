<?php

namespace Drupal\slick\Form;

use Drupal\blazy\Form\BlazyAdminInteropInterface;

/**
 * Provides resusable admin functions or form elements.
 *
 * @todo recheck if to extend BlazyAdminInterface. The reason it never extends
 * it is to avoid blocking Blazy adjustments due to still recognizing similar
 * features across sub-modules to DRY.
 */
interface SlickAdminInterface extends BlazyAdminInteropInterface {

  /**
   * Returns the blazy admin formatter.
   */
  public function blazyAdmin();

  /**
   * Returns the slick manager.
   */
  public function manager();

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions(): array;

  /**
   * Returns available slick optionsets by group.
   */
  public function getOptionsetsByGroupOptions($group = ''): array;

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions(): array;

  /**
   * Returns available slick skins for select options.
   */
  public function getSkinsByGroupOptions($group = ''): array;

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary(array $definition = []): array;

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions(
    array $target_bundles = [],
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
  ): array;

}
