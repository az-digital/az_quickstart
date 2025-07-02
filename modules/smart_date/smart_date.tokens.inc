<?php

/**
 * @file
 * Provides tokens for the smart_date module.
 */

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateFieldItemList;

/**
 * Implements hook_token_info().
 */
function smart_date_token_info() {
  if (!\Drupal::hasService('token.entity_mapper')) {
    return;
  }

  $types = [];
  $tokens = [];
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type)) {
      continue;
    }

    // Build property tokens for all smart date fields.
    $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field) {
      if ($field->getType() != 'smartdate') {
        continue;
      }

      $tokens[$token_type . '-' . $field_name]['value-custom'] = [
        'name' => t('Start, custom format'),
        'description' => NULL,
        'dynamic' => TRUE,
        'module' => 'smart_date',
      ];
      $tokens[$token_type . '-' . $field_name]['end_value-custom'] = [
        'name' => t('End, custom format'),
        'description' => NULL,
        'dynamic' => TRUE,
        'module' => 'smart_date',
      ];
      $tokens[$token_type . '-' . $field_name]['format'] = [
        'name' => t('Formatted by Smart Date Format'),
        'description' => t('Provide the id of a specific smart date format to use it for formatting.'),
        'dynamic' => TRUE,
        'module' => 'smart_date',
      ];
      $tokens[$token_type . '-' . $field_name]['value-format'] = [
        'name' => t('Start, formatted by Smart Date Format'),
        'description' => t('Provide the id of a specific smart date format to use it for formatting.'),
        'dynamic' => TRUE,
        'module' => 'smart_date',
      ];
      $tokens[$token_type . '-' . $field_name]['end_value-format'] = [
        'name' => t('End, formatted by Smart Date Format'),
        'description' => t('Provide the id of a specific smart date format to use it for formatting.'),
        'dynamic' => TRUE,
        'module' => 'smart_date',
      ];
    }
  }

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function smart_date_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if (empty($data['field_property'])) {
    return $replacements;
  }

  foreach ($tokens as $token => $original) {
    $list = $data[$data['field_name']];
    if (!$list instanceof SmartDateFieldItemList) {
      continue;
    }

    $parts = explode(':', $token, 2);
    // Test for a delta as the first part.
    if (is_numeric($parts[0])) {
      if (count($parts) > 1) {
        $parts = explode(':', $parts[1], 2);
        $property_name = $parts[0];
        $format_value = $parts[1] ?? NULL;
      }
      else {
        continue;
      }
    }
    else {
      $property_name = $parts[0];
      $format_value = $parts[1] ?? NULL;
    }
    // Now parse out the pieces of the token name.
    $name_parts = explode('-', $property_name);
    $approach = array_pop($name_parts);
    $field = $list->first();
    if ($approach == 'custom') {
      if (!$format_value) {
        // This token requires a value, so skip if absent.
        continue;
      }
      // Get the requested property and apply the provided format.
      if ($name_parts && $prop_needed = array_pop($name_parts)) {
        $field_ts = $field->get($prop_needed)->getValue();
        $replacements[$original] = \Drupal::service('date.formatter')->format($field_ts, '', $format_value);
      }
    }
    elseif ($approach == 'format') {
      if (!$format_value) {
        // Our tokens require a value, so skip if absent.
        $format_value = 'default';
      }
      $format = SmartDateFormat::load($format_value);
      if (!$format) {
        \Drupal::messenger()->addError(t('Unable to load specified Smart Date format: @format', ['@format' => $format_value]));
        if ($format_value === 'default') {
          return $replacements;
        }
        else {
          $format = SmartDateFormat::load('default');
          if (!$format) {
            \Drupal::messenger()->addError(t('Unable to load default Smart Date format'));
            return $replacements;
          }
        }
      }
      $settings = $format->getOptions();
      // Apply the specified smart date format.
      // If a property was specified, only use that.
      if ($name_parts && $prop_needed = array_pop($name_parts)) {
        $field_ts = $field->get($prop_needed)->getValue();
        $replacements[$original] = \Drupal::service('smart_date.manager')->formatSmartDate($field_ts, $field_ts, $settings, NULL, 'string');
      }
      // Otherwise, format the whole range.
      else {
        $start = $field->get('value')->getValue();
        $end = $field->get('end_value')->getValue();
        $replacements[$original] = \Drupal::service('smart_date.manager')->formatSmartDate($start, $end, $settings, NULL, 'string');
      }
    }
  }
  return $replacements;
}
