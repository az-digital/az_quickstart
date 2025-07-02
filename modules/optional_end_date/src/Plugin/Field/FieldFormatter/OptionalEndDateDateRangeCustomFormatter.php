<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldFormatter;

use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeCustomFormatter;
use Drupal\optional_end_date\OptionalEndDateDateTimeRangeTrait;

/**
 * Override the custom formatter.
 */
class OptionalEndDateDateRangeCustomFormatter extends DateRangeCustomFormatter {

  use OptionalEndDateDateTimeRangeTrait;

}
