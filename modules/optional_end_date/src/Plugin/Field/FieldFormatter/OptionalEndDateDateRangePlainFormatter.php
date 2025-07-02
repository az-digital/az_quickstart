<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldFormatter;

use Drupal\datetime_range\Plugin\Field\FieldFormatter\DateRangePlainFormatter;
use Drupal\optional_end_date\OptionalEndDateDateTimeRangeTrait;

/**
 * Override the plain formatter.
 */
class OptionalEndDateDateRangePlainFormatter extends DateRangePlainFormatter {

  use OptionalEndDateDateTimeRangeTrait;

}
