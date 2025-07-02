<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    return TRUE;
  }

}
