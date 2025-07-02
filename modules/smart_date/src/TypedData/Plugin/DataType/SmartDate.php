<?php

namespace Drupal\smart_date\TypedData\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

/**
 * The SmartDate data type.
 *
 * @DataType(
 *   id = "smart_date",
 *   label = @Translation("Smart Date")
 * )
 */
class SmartDate extends Timestamp {

  /**
   * The data value as a UNIX timestamp.
   *
   * @var int
   */
  protected $value;

  /**
   * The end value of the event.
   *
   * @var mixed
   */
  protected $end_value; // phpcs:ignore

  /**
   * The duration of the event.
   *
   * @var mixed
   */
  protected $duration;

  /**
   * The recurrence rule of the event.
   *
   * @var mixed
   */
  protected $rrule;

  /**
   * The index of the recurrence rule.
   *
   * @var mixed
   */
  protected $rrule_index; // phpcs:ignore

  /**
   * The timezone of the event.
   *
   * @var mixed
   */
  protected $timezone;

  /**
   * {@inheritdoc}
   */
  public function getDateTime() {
    if (isset($this->value)) {
      return DrupalDateTime::createFromTimestamp($this->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, $notify = TRUE) {
    $this->value = $dateTime->getTimestamp();
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
