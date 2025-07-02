<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldFormatter;

use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangeDefaultFormatter;
use Drupal\optional_end_date\OptionalEndDateDateTimeRangeTrait;

/**
 * Override the default formatter.
 */
class OptionalEndDateDateRangeDefaultFormatter extends DateRangeDefaultFormatter {

  use OptionalEndDateDateTimeRangeTrait;

}
