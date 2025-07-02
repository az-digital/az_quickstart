<?php

namespace Drupal\smart_date\Normalizer;

use Drupal\serialization\Normalizer\TimestampNormalizer;
use Drupal\smart_date\TypedData\Plugin\DataType\SmartDate;

/**
 * Enhances the smart date field so it can be denormalized.
 */
class SmartDateNormalizer extends TimestampNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = SmartDate::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []): mixed {

    // Check if $data is a string and convert it to an array if necessary.
    if (is_string($data)) {
      $data = ['value' => $data];
    }

    // Ensure $data is an array before proceeding.
    if (!is_array($data)) {
      throw new \InvalidArgumentException('Expected data to be a string or array.');
    }

    if (!empty($data['format'])) {
      // REST request sender may provide own data format, try to deploy it.
      // Parent classes override $format anyway.
      $context['datetime_allowed_formats'] =
        empty($context['datetime_allowed_formats']) ? [] : $context['datetime_allowed_formats'] + ['user_format' => $data['format']];
    }
    /*
    @todo check this suggestion
    not sure if this needed, seems properties should go from
    \TypedData\Plugin\DataType\SmartData and fall down to existing
    serializers may be via
    TypedDataInternalPropertiesHelper::getNonInternalProperties()
    but most inheritance done from Timestamps.
     */
    // Safely handle optional fields by checking their existence.
    $res = [
      'value' => isset($data['value']) ? parent::denormalize($data['value'], $class, $format, $context) : NULL,
      'end_value' => isset($data['end_value']) ? parent::denormalize($data['end_value'], $class, $format, $context) : NULL,
      'duration' => $data['duration'] ?? NULL,
      'rrule' => $data['rrule'] ?? NULL,
      'rrule_index' => $data['rrule_index'] ?? NULL,
      'timezone' => $data['timezone'] ?? NULL,
    ];

    return $res;
  }

}
