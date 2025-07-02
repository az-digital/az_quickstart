<?php

namespace Drupal\calendar_link\Twig;

use Drupal\calendar_link\CalendarLinkException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList;
use Spatie\CalendarLinks\Exceptions\InvalidLink;
use Spatie\CalendarLinks\Link;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extensions class for the `calendar_link` and `calender_links` functions.
 *
 * @package Drupal\calendar_link\Twig
 */
class CalendarLinkTwigExtension extends AbstractExtension {
  use StringTranslationTrait;

  /**
   * Available link types (generators).
   *
   * @var array
   *
   * @see \Spatie\CalendarLinks\Link
   */
  protected static array $types = [
    'google' => 'Google',
    'ics' => 'iCal',
    'yahoo' => 'Yahoo!',
    'webOutlook' => 'Outlook.com',
    'webOffice' => 'Office365',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction('calendar_link', [$this, 'calendarLink']),
      new TwigFunction('calendar_links', [$this, 'calendarLinks']),
    ];
  }

  /**
   * Create a calendar link.
   *
   * All data parameters accept multiple types of data and will attempt to get
   * the relevant information from e.g. field instances or content arrays.
   *
   * @param string $type
   *   Generator key to use for link building.
   * @param mixed $title
   *   Calendar entry title.
   * @param mixed $from
   *   Calendar entry start date and time.
   * @param mixed $to
   *   Calendar entry end date and time.
   * @param mixed $all_day
   *   Indicator for an "all day" calendar entry.
   * @param mixed $description
   *   Calendar entry description.
   * @param mixed $address
   *   Calendar entry address.
   *
   * @return string
   *   URL for the specific calendar type.
   */
  public function calendarLink(string $type, mixed $title, mixed $from, mixed $to, mixed $all_day = FALSE, mixed $description = '', mixed $address = ''): string {
    if (!isset(self::$types[$type])) {
      throw new CalendarLinkException('Invalid calendar link type.');
    }

    try {
      $link = Link::create(
        $this->getString($title),
        $this->getDateTime($from),
        $this->getDateTime($to),
        $this->getBoolean($all_day)
      );
    }
    catch (InvalidLink $e) {
      throw new CalendarLinkException($e->getMessage());
    }

    if ($description) {
      $link->description($this->getString($description));
    }

    if ($address) {
      $link->address($this->getString($address));
    }

    return $link->{$type}();
  }

  /**
   * Create links for all calendar types.
   *
   * All parameters accept multiple types of data and will attempt to get the
   * relevant information from e.g. field instances or content arrays.
   *
   * @param mixed $title
   *   Calendar entry title.
   * @param mixed $from
   *   Calendar entry start date and time. This value can be various DateTime
   *   types, a content field array, or a field.
   * @param mixed $to
   *   Calendar entry end date and time. This value can be various DateTime
   *   types, a content field array, or a field.
   * @param mixed $all_day
   *   Indicator for an "all day" calendar entry.
   * @param mixed $description
   *   Calendar entry description.
   * @param mixed $address
   *   Calendar entry address.
   *
   * @return array
   *   - type_key: Machine key for the calendar type.
   *   - type_name: Human-readable name for the calendar type.
   *   - url: URL for the specific calendar type.
   *
   * @see \Drupal\calendar_link\Twig\CalendarLinkTwigExtension::calendarLink()
   */
  public function calendarLinks(mixed $title, mixed $from, mixed $to, mixed $all_day = FALSE, mixed $description = '', mixed $address = ''): array {
    $links = [];

    foreach (self::$types as $type => $name) {
      $links[$type] = [
        'type_key' => $type,
        'type_name' => $name,
        'url' => $this->calendarLink($type, $title, $from, $to, $all_day, $description, $address),
      ];
    }

    return $links;
  }

  /**
   * Gets a boolean value from various types of input.
   *
   * @param mixed $data
   *   A value with a boolean value.
   *
   * @return bool
   *   Boolean from data.
   *
   * @throws \Drupal\calendar_link\CalendarLinkException
   */
  private function getBoolean(mixed $data): bool {
    if (is_bool($data)) {
      return $data;
    }

    try {
      $data = $this->getString($data);
      return (bool) $data;
    }
    catch (CalendarLinkException) {
      throw new CalendarLinkException('Could not get valid boolean from input.');
    }
  }

  /**
   * Gets a string value from various types of input.
   *
   * @param mixed $data
   *   A value with a string.
   *
   * @return string
   *   String from data.
   *
   * @throws \Drupal\calendar_link\CalendarLinkException
   */
  private function getString(mixed $data): string {
    // Content field array. E.g. `label`.
    if (is_array($data)) {
      if (isset($data['#items'])) {
        $data = $data['#items'];
      }
      else {
        // If not "#items" key is present the field is empty.
        return '';
      }
    }

    // Drupal field instance. E.g. `node.title`.
    if ($data instanceof FieldItemListInterface) {
      $data = $data->getString();
    }

    // Other common object string representation methods, if available.
    if (is_object($data)) {
      if (method_exists($data, '__toString')) {
        $data = (string) $data;
      }
      elseif (method_exists($data, 'toString')) {
        $data = $data->toString();
      }
    }

    if (is_int($data)) {
      return (string) $data;
    }

    if (is_string($data)) {
      return $data;
    }

    throw new CalendarLinkException('Could not get string from input type ' . get_class($data) . '.');
  }

  /**
   * Gets a PHP \DateTime instance from various types of input.
   *
   * @param mixed $date
   *   A value with \DateTime data.
   *
   * @return \DateTime
   *   The \DateTime instance.
   *
   * @throws \Drupal\calendar_link\CalendarLinkException
   */
  private function getDateTime(mixed $date): \DateTime {
    // Content field array. E.g. `content.field_start`.
    if (is_array($date) && isset($date['#items'])) {
      $date = $date['#items'];
    }

    // Drupal field instance. E.g. `node.field_start`.
    if ($date instanceof DateTimeFieldItemList) {
      $date = $date->date;
    }

    if ($date instanceof \DateTime) {
      return $date;
    }

    if ($date instanceof DrupalDateTime) {
      return $date->getPhpDateTime();
    }

    // Drupal date range field instance. E.g. `node.field_date_range.end_value`.
    if (is_string($date)) {
      // Attempt to parse the input string as a date and time.
      $parsed_date = date_create($date);
      if ($parsed_date !== false) {
        return $parsed_date;
      }
    }

    // Attempt to parse an HTML `time` element (Views default behavior). Only
    // the default formatter is supported as other formatters do not guarantee
    // accurate timezone data.
    /** @var \DOMElement $element */
    $element = Html::load($date)->getElementsByTagName('time')->item(0);
    if ($element && $element->hasAttribute('datetime')) {
      $date = new DrupalDateTime($element->getAttribute('datetime'));
      if (!$date->hasErrors()) {
        return $date->getPhpDateTime();
      }
    }

    throw new CalendarLinkException('Could not get date and time from input value ' . $date . '.');
  }

}

