<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\TimestampItem;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'smartdate' field type.
 *
 * @FieldType(
 *   id = "smartdate",
 *   label = @Translation("Smart date range"),
 *   description = {
 *     @Translation("Create and store events as timestamp ranges, for maximum performance."),
 *     @Translation("Able to handle timezones and recurring dates (with an optional submodule)"),
 *     @Translation("Provides an intuitive widget for easy entry, natural language formatting, and handles all day events too"),
 *   },
 *   category = "date_time",
 *   default_widget = "smartdate_inline",
 *   default_formatter = "smartdate_default",
 *   list_class = "\Drupal\smart_date\Plugin\Field\FieldType\SmartDateFieldItemList",
 *   constraints = {
 *     "ComplexData" = {
 *       "value" = {
 *         "Range" = {
 *           "min" = "-9223372036854775807",
 *           "max" = "9223372036854775807",
 *         }
 *       },
 *       "end_value" = {
 *         "Range" = {
 *           "min" = "-9223372036854775807",
 *           "max" = "9223372036854775807",
 *         }
 *       },
 *       "duration" = {
 *         "Range" = {
 *           "min" = "0",
 *           "max" = "2147483647",
 *         }
 *       },
 *     }
 *   }
 * )
 */
class SmartDateItem extends TimestampItem {

  /**
   * The stored field delta.
   *
   * @var int
   */
  public $delta;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $properties['start_time'] = DataDefinition::create('any')
      ->setLabel(t('Computed start date'))
      ->setDescription(t('The computed start DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'value');

    $properties['end_value'] = DataDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    $properties['end_time'] = DataDefinition::create('any')
      ->setLabel(t('Computed end date'))
      ->setDescription(t('The computed end DateTime object.'))
      ->setComputed(TRUE)
      ->setClass(DateTimeComputed::class)
      ->setSetting('date source', 'end_value');

    $properties['duration'] = DataDefinition::create('integer')
      ->setLabel(t('Duration, in minutes'))
      // @todo figure out a way to validate as required but accept zero.
      ->setRequired(FALSE);

    $properties['rrule'] = DataDefinition::create('integer')
      ->setLabel(t('RRule ID'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(FALSE);

    $properties['rrule_index'] = DataDefinition::create('integer')
      ->setLabel(t('RRule Index'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(FALSE);

    $timezones = \DateTimeZone::listIdentifiers();
    array_unshift($timezones, '');

    $properties['timezone'] = DataDefinition::create('string')
      ->setLabel(t('Timezone'))
      ->setDescription(t('The timezone of this date.'))
      ->setSetting('max_length', 32)
      ->setRequired(FALSE)
      // @todo Define this via an options provider once
      // https://www.drupal.org/node/2329937 is completed.
      ->addConstraint('AllowedValues', $timezones);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'description' => 'The start time value.',
          'type' => 'int',
          'size' => 'big',
        ],
        'end_value' => [
          'description' => 'The end time value.',
          'type' => 'int',
          'size' => 'big',
        ],
        'duration' => [
          'description' => 'The difference between start and end times, in minutes.',
          'type' => 'int',
        ],
        'rrule' => [
          'description' => 'The ID an associated recurrence rule.',
          'type' => 'int',
        ],
        'rrule_index' => [
          'description' => 'The Index of an associated recurrence rule instance.',
          'type' => 'int',
        ],
        'timezone' => [
          'description' => 'The preferred timezone.',
          'type' => 'varchar',
          'length' => 32,
        ],
      ],
      'indexes' => [
        'value' => [
          'value',
        ],
        'end_value' => [
          'end_value',
        ],
        'rrule' => [
          'rrule',
        ],
        'rrule_index' => [
          'rrule_index',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Pick a random timestamp in the past year.
    $timestamp = \Drupal::time()->getRequestTime() - mt_rand(0, 86400 * 365);
    $timestamp = floor($timestamp / 60) * 60;
    $duration = 60;
    $values['value'] = $timestamp;
    $values['end_value'] = $timestamp + $duration * 60;
    $values['duration'] = $duration;
    $values['timezone'] = 'UTC';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $start_value = $this->get('value')->getValue();
    $end_value = $this->get('end_value')->getValue();
    return ($start_value === NULL || $start_value === '') && ($end_value === NULL || $end_value === '');
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Enforce that the computed date is recalculated.
    if ($property_name == 'value') {
      // @phpstan-ignore-next-line
      $this->start_time = NULL;
    }
    elseif ($property_name == 'end_value') {
      // @phpstan-ignore-next-line
      $this->end_time = NULL;
    }
    parent::onChange($property_name, $notify);
  }

}
