<?php

namespace Drupal\az_publication_bibtex\Processor;

use RenanBr\BibTexParser\Processor\TagSearchTrait;

/**
 * Processes various types of BibTeX dates.
 */
class AZDateProcessor {
  use TagSearchTrait;
  const TAG_NAME = '_date';
  const DATE_TYPE_NAME = '_date_type';

  /**
   * @var string
   */
  private $tagName;

  /**
   * @var string
   */
  private $dateTypeName;

  /**
   * @param string $tagName
   *   The default tag to use for the date.
   */
  public function __construct($tagName = NULL) {
    $this->tagName = $tagName ?: self::TAG_NAME;
    $this->dateTypeName = self::DATE_TYPE_NAME;
  }

  /**
   * @return array
   *   The associative citation fields.
   */
  public function __invoke(array $entry) {
    $yearTag = $this->tagSearch('year', array_keys($entry));
    $monthTag = $this->tagSearch('month', array_keys($entry));
    if (NULL !== $yearTag) {
      $year = (int) $entry[$yearTag];
      $day = 1;
      $month = 1;
      $dateType = 'year';
      if (NULL !== $monthTag) {
        $monthArray = explode('~', $entry[$monthTag]);
        $count = \count($monthArray);
        // Month only.
        if (1 === $count) {
          $month = reset($monthArray);
          $dateType = 'month';
          $dateMonthNumber = date_parse($month);
          $month = $dateMonthNumber['month'] ?: 1;
        }
        // Day and month, eg. 1~jan.
        elseif (2 === $count) {
          $day = $monthArray[0];
          $month = $monthArray[1];
          $dateType = 'default';
          $day = (int) $day;
          $dateMonthNumber = date_parse($month);
          $month = $dateMonthNumber['month'] ?: 1;
        }
      }
      if (checkdate($month, $day, $year)) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        $dateObj = new \DateTimeImmutable(date('Y-m-d', $timestamp), new \DateTimeZone('UTC'));
        $entry[$this->tagName] = date_format($dateObj, 'Y-m-d');
        $entry[$this->dateTypeName] = $dateType;
      }
    }

    return $entry;
  }

}
