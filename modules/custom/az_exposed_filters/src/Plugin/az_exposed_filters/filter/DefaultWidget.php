<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

/**
 * Default widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    return TRUE;
  }

}
