<?php

namespace Drupal\blazy_layout;

use Drupal\blazy\BlazyManagerInterface;

/**
 * Defines re-usable services and functions for BlazyLayoutManager.
 */
interface BlazyLayoutManagerInterface extends BlazyManagerInterface {

  /**
   * Returns CSS classes.
   *
   * @param array $settings
   *   The settings array.
   *
   * @return array
   *   The CSS classes, if any.
   */
  public function getClasses(array $settings): array;

  /**
   * Returns the element keys.
   *
   * @param array $elements
   *   The array elements.
   *
   * @return array
   *   The element keys.
   */
  public function getKeys(array $elements): array;

  /**
   * Returns the region array based on the region amount.
   *
   * @param int|null $count
   *   The amount of region, default to 9.
   *
   * @return array
   *   The region array.
   */
  public function getRegions($count = NULL): array;

  /**
   * Returns updated settings.
   *
   * @param array $settings
   *   The settings array.
   * @param int $count
   *   The amount of region, default to 9.
   *
   * @return array
   *   The updated settings.
   */
  public function layoutSettings(array $settings, $count): array;

  /**
   * Modifies output classes.
   *
   * @param array $output
   *   The output being modified.
   * @param array $settings
   *   The settings array.
   */
  public function parseClasses(array &$output, array $settings): void;

  /**
   * Provides CSS selector.
   *
   * @param string $key
   *   The key based on the form item option, e.g.: background.
   * @param string $region
   *   The region name.
   * @param array $options
   *   The options array.
   *
   * @return string
   *   The CSS selector.
   */
  public function selector($key, $region, array $options = []): string;

  /**
   * Converts data array to CSS rules.
   *
   * @param array $data
   *   The data array containing selectors and their CSS rules.
   * @param string $id
   *   The layout instance ID.
   *
   * @return string
   *   The CSS rules.
   */
  public function toRules(array $data, $id): string;

  /**
   * Returns the available admin theme to fetch the media library styling.
   *
   * @todo remove, useless.
   */
  public function getMediaLibraries(): array;

}
