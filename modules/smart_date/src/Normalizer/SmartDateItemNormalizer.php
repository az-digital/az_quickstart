<?php

namespace Drupal\smart_date\Normalizer;

use Drupal\serialization\Normalizer\TimestampItemNormalizer;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem;
use Drupal\smart_date\TypedData\Plugin\DataType\SmartDate;

/**
 * Converts values for TimestampItem to and from common formats.
 *
 * Overrides FieldItemNormalizer to use
 * \Drupal\serialization\Normalizer\TimestampNormalizer.
 *
 * Overrides FieldItemNormalizer to
 * - during normalization, add the 'format' key to assist consumers
 * - during denormalization, use
 *   \Drupal\serialization\Normalizer\TimestampNormalizer
 */
class SmartDateItemNormalizer extends TimestampItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = SmartDateItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return parent::normalize($object, $format, $context) + [
      // 'format' is not a property on Timestamp objects. This is present to
      // assist consumers of this data.
      'format' => \DateTime::RFC3339,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    if (!empty($data['format'])) {
      $context['datetime_allowed_formats'] = [$data['format']];
    }
    return $this->serializer->denormalize($data, SmartDate::class, NULL, $context);
  }

}
