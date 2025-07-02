<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Abstract class meant to expose parse and related functions for lists.
 */
abstract class SmartDateListItemBase extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function parseValues($values) {
    // Use the ListItemBase parsing function, but don't allow generated keys.
    if (!class_exists(DeprecationHelper::class)) {
      return static::extractAllowedValues($values, 1);
    }
    return DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.2',
      static function () use ($values) {
        $list = (is_array($values)) ? $values : explode("\n", $values);
        $list = array_map('trim', $list);
        $list = array_filter($list, 'strlen');
        return static::extractAllowedValues($list, 1);
      },
      static fn () => self::extractAllowedValues($values, 1)
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    // Verify that the duration option is either custom or an integer.
    if (($option != 'custom') && !preg_match('/^-?\\d+$/', $option)) {
      return t('Allowed values list: keys must be integers or "custom".');
    }
  }

}
