<?php

namespace Drupal\az_news_export\Normalizer;

use Drupal\az_news_export\AZNewsDataEmpty;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizes AZNewsDataEmpty objects into an empty object.
 */
class AZNewsDataEmptyNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    return new \ArrayObject();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      AZNewsDataEmpty::class => TRUE,
    ];
  }

}
