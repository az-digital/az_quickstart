<?php

namespace Drupal\smart_date\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\smart_date_recur\Entity\SmartDateRule;

/**
 * Given Drupal 7 dates or serialized values parse for a Smart Date field.
 *
 * Three formats of incoming data are accepted:
 * - Drupal 7 Date values of the form
 *   `['value' => START, 'value2' => END, 'rrule' => STRING]`
 *   (where rrule is optional);
 * - Views' "Date and Time" format, which you might use in an XML data export
 *   `<span class="date-display-start"
 *   ...>START</span> to <span class="date-display-end" ...>END</span>`
 *   with an optional `<div class="date-repeat-rule">STRING</div>` if you enable
 *   that option in the field of the View;
 * - or plain text serialized `START to END`.
 * The date values themselves can be in any format that works with PHP's
 * strtotime() function.
 *
 * Example:
 *
 * @code
 * process:
 *  field_smart_date:
 *    plugin: parse_dates
 *    source: field_date
 *    entity_type: node
 *    bundle: opportunity
 * @endcode
 *
 * The entity_type and bundle are only necessary for repeating dates.
 *
 * @MigrateProcessPlugin(
 *   id = "parse_dates"
 * )
 */
class ParseDates extends ProcessPluginBase {

  protected const ONE_DAY = 24 * 60 * 60;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      // Check for multiple serialized values.
      foreach (['|', ';', ', ', ','] as $delimiter) {
        if (strpos($value, $delimiter)) {
          $value = explode($delimiter, $value);
          break;
        }
      }
      $value = (array) $value;
    }
    $rrule = NULL;
    $k = NULL;
    $ordinal = [
      'first' => '+1',
      'second' => '+2',
      'third' => '+3',
      'fourth' => '+4',
      'last' => '-1',
    ];
    $month = [
      'January' => 1,
      'February' => 2,
      'March' => 3,
      'April' => 4,
      'May' => 5,
      'June' => 6,
      'July' => 7,
      'August' => 8,
      'September' => 9,
      'October' => 10,
      'November' => 11,
      'December' => 12,
    ];
    // @phpstan-ignore-next-line
    $timezone = \Drupal::config('system.date')->get('timezone')['default'];
    foreach ($value as $k => $d) {
      // Put the values into a standard format so we can work with them below.
      if (!is_array($d)) {
        // The format we want is:
        // ['value' => START, 'value2' => END, 'rrule' => STRING].
        if (strpos($d, 'date-display')) {
          // Parse Views' "Date and Time" HTML format to a D7 style array. Views
          // changes the rrule from the standard format to human-readable text,
          // so we have to change it back.
          if (preg_match('#Repeats every (\d*)? ?(day|week|month|year)s? +(on .*)? (\d+ times|until .*)\.#', $d, $m)) {
            $rrule = "FREQ=" . ($m[2] == 'day' ? 'DAI' : strtoupper($m[2])) . "LY;INTERVAL=" . ($m[1] ?: 1);
            if (preg_match_all('#(\w\w)\w+day#', $m[3], $y)) {
              $rrule .= ";BYDAY=" . strtoupper(implode(',', $y[1]));
            }
          }
          elseif (preg_match('#Repeats on the (\w+) (\w+) of the (week|month|year) (\d+ times|until .*)\.#', $d, $m)) {
            $rrule = "FREQ=" . ($m[3] == 'day' ? 'DAI' : strtoupper($m[3])) . "LY;INTERVAL=1";
            $rrule .= ";BYDAY=" . $ordinal[$m[1]] . strtoupper(substr($m[2], 0, 2));
          }
          elseif (preg_match('#Repeats on the (\w+) (\w+) of the month during (.*) (\d+ times|until .*)\.#', $d, $m)) {
            $rrule = "RRULE:FREQ=MONTHLY;INTERVAL=1";
            $months = [];
            foreach (explode(' ', $m[3]) as $mo) {
              $mo = trim($mo, ',');
              if (isset($month[$mo])) {
                $months[] = $month[$mo];
              }
            }
            $rrule .= ";BYDAY=" . $ordinal[$m[1]] . strtoupper(substr($m[2], 0, 2)) . ";BYMONTH=" . implode(',', $months);
          }
          elseif (preg_match('#Repeats on the (\d+)\w+ of (.*) (\d+ times|until .*)\.#', $d, $m)) {
            $rrule = "RRULE:FREQ=MONTHLY;INTERVAL=1";
            $months = [];
            foreach (explode(' ', $m[2]) as $mo) {
              $mo = trim($mo, ',');
              if (isset($month[$mo])) {
                $months[] = $month[$mo];
              }
            }
            $rrule .= ";BYMONTH=" . implode(',', $months) . ";BYMONTHDAY=" . $m[1];
            $m[4] = $m[3];
          }
          if (isset($m[4])) {
            if (strpos($m[4], 'times')) {
              $rrule .= ';COUNT=' . substr($m[4], 0, -6);
            }
            else {
              $rrule .= ';UNTIL=' . $this->date('Ymd\THis\Z', strtotime(substr($m[4], 6)), $timezone);
            }
          }
          // In the "Date and Time" format, there are either "start" & "end"
          // values, or a "single" value.
          if (!preg_match('#"date-display-start"[^>]*>([^<]*).*"date-display-end"[^>]*>([^<]*)#', $d, $m)) {
            preg_match('#"date-display-single"[^>]*>([^<]*)#', $d, $m);
            // A single time of midnight meant "all day" in the D7 Date module.
            // The Smart Date equivalent requires an end_value of 23:59:00,
            // or a minute shy of midnight.
            $m[2] = ($this->date('H:i:s', $m[1], $timezone) == '00:00:00') ? ($m[1] + self::ONE_DAY - 60) : $m[1];
          }
          $d = [
            'value' => $m[1],
            'value2' => $m[2],
            'rrule' => $rrule,
          ];
        }
        else {
          // Parse plain text serialized values (with " to " between the START &
          // END) to a Drupal 7 style array.
          // So much simpler than "Date and Time" format!
          $d = explode(' to ', $d);
          $d = [
            'value' => $d[0],
            'value2' => $d[1] ?? $d[0],
          ];
        }
      }

      // Now that we've sorted out the start & end values, we're finally ready
      // to work with them.
      if ((int) $d['value'] != $d['value']) {
        // Convert string values to UNIX timestamps for consistent processing.
        $d['value'] = strtotime($d['value']);
        $d['value2'] = strtotime($d['value2']);
      }
      if ($d['value2'] > 2147483647) {
        // This value is out of range due to the Year 2038 Bug.
        // https://en.wikipedia.org/wiki/Year_2038_problem
        // Although PHP is unfazed by the bug, MySQL and MariaDB are afflicted,
        // so we can't store these values.
        unset($d);
        continue;
      }
      if (!isset($d['rrule']) && $d['value2'] - $d['value'] > self::ONE_DAY) {
        // Change a multi-day event into a single day event that repeats.
        $end = $d['value2'];
        $d['rrule'] = 'RRULE:FREQ=DAILY;INTERVAL=1;UNTIL=' . $this->date('Ymd\THis\Z', $end, $timezone);
        $d['value2'] = strtotime($this->date('Y-m-d', $d['value'], $timezone) . $this->date(' H:i:s', $d['value2'], $timezone));
        // If we accidentally wrapped to the previous morning due to the time
        // zone, add a day back.
        if ($d['value2'] < $d['value']) {
          $d['value2'] += self::ONE_DAY;
        }
        $value[$k] = $d;
        // Generate new values for the repetitions.
        while ($d['value2'] < $end) {
          $value[] = [
            'value' => $d['value'] += self::ONE_DAY,
            'value2' => $d['value2'] += self::ONE_DAY,
            'rrule' => $d['rrule'],
          ];
        }
        // Don't loop over the values we just created.
        break;
      }
      if (isset($d['rrule']) && $d['value2'] - $d['value'] > self::ONE_DAY) {
        // Durations of repeating events should also be no more than 24 hours.
        while ($d['value2'] - $d['value'] > self::ONE_DAY) {
          $d['value2'] -= self::ONE_DAY;
        }
      }
      $value[$k] = $d;
    }

    // Now that the data is cleaned up, convert it to Smart Date format.
    $dates = $rdata = [];
    $counter = 0;
    foreach ($value as $k => $d) {
      // Skip any values that got unset above.
      if (!is_array($d)) {
        continue;
      }
      if (isset($d['rrule']) && $d['rrule'] > '') {
        if (isset($rdata['rule']) && $d['rrule'] != $rdata['rule']) {
          // Time for a new rrule. Save the old rule before continuing.
          $rrule = SmartDateRule::create([$rdata]);
          foreach ($rdata as $f => $v) {
            $rrule->set($f, $v);
          }
          $rrule->save();
          // Now that we have an rrule ID, process the field values that belong
          // to that rule.
          $ri = 1;
          for ($i = $counter; $i < $k; $i++) {
            $dates[$i] = [
              'value' => $value[$i]['value'],
              'end_value' => $value[$i]['value2'],
              'duration' => (int) round(($value[$i]['value2'] - $value[$i]['value']) / 60),
              'rrule' => $rrule->id(),
              'rrule_index' => $ri,
              'timezone' => $timezone,
            ];
          }
          $counter = $k;
        }
        if (!isset($rdata['rule']) || $d['rrule'] != $rdata['rule']) {
          // First check for length. Smart Date Rules are limited to 256
          // characters.
          if (strlen($d['rrule']) > 255 && preg_match('/EXDATE:[^;]+/', $d['rrule'], $m)) {
            preg_match_all('/\d{8}T\d{6}Z,?/', $m[0], $m);
            foreach ($m[0] as $t) {
              // Delete any EXDATEs in the past to reduce the rule length.
              if (substr($t, 0, 8) < date('Ymd')) {
                $d['rrule'] = str_replace($t, '', $d['rrule']);
              }
            }
          }
          $newrule = $d['rrule'];
          // Check the rrule for Year 2038 Bug compliance.
          preg_match('/FREQ=([^;]+).*((COUNT|UNTIL)=([^;])+)/', $d['rrule'], $m);
          $freq = [
            'DAILY' => self::ONE_DAY,
            'WEEKLY' => 7 * self::ONE_DAY,
            'MONTHLY' => 31 * self::ONE_DAY,
            'YEARLY' => 366 * self::ONE_DAY,
          ];
          $limit = $m[2];
          if ($m[3] == 'COUNT') {
            $enddate = (int) $d['value2'] + $m[4] * $freq[$m[1]];
            if ($enddate > 2147483647) {
              $limit = 'COUNT=' . (int) ((2147483647 - (int) $d['value2']) / $freq[$m[1]]);
              $newrule = str_replace($m[2], $limit, $d['rrule']);
            }
          }
          elseif ($m[3] == 'UNTIL') {
            $enddate = strtotime($m[4]);
            if ($enddate > 2147483647) {
              $limit = 'UNTIL=' . $this->date('Ymd\THis\Z', 2147483647, $timezone);
              $newrule = str_replace($m[2], $limit, $d['rrule']);
            }
          }
          elseif (!isset($m[2])) {
            $limit = 'UNTIL=' . $this->date('Ymd\THis\Z', 2147483647, $timezone);
            $newrule .= ';' . $limit;
          }
          if ($newrule != $d['rrule']) {
            // Change all instances of the noncompliant rrule to the newrule so
            // we can reuse it.
            foreach ($value as &$i) {
              if ($i['rrule'] == $d['rrule']) {
                $i['rrule'] = $newrule;
              }
            }
            $d['rrule'] = $newrule;
          }
          // Create a new rule with this as the first instance.
          $rdata = [
            'start' => $d['value'],
            'end' => $d['value2'],
            'rule' => $d['rrule'],
            'freq' => $m[1],
            'limit' => $limit,
            'instances' => [
              'data' => [
                1 => [
                  'value' => $d['value'],
                  'end_value' => $d['value2'],
                ],
              ],
            ],
            'entity_type' => $this->configuration['entity_type'],
            'bundle' => $this->configuration['bundle'],
            'field_name' => $destination_property,
          ];
        }
        else {
          // Add this instance to an existing rule.
          $rdata['instances']['data'][] = [
            'value' => $d['value'],
            'end_value' => $d['value2'],
          ];
        }

      }
      else {
        // This date doesn't repeat.
        $dates[$k] = [
          'value' => $d['value'],
          'end_value' => $d['value2'],
          'duration' => (int) round(($d['value2'] - $d['value']) / 60),
          'timezone' => $timezone,
        ];
        $rdata = [];
        $counter = $k + 1;
      }
    }
    if (isset($rdata['rule']) && $k) {
      // Save the rule.
      $rrule = SmartDateRule::create([$rdata]);
      foreach ($rdata as $f => $v) {
        $rrule->set($f, $v);
      }
      $rrule->save();
      // Process field values belonging to this rule.
      $ri = 1;
      for ($i = $counter; $i <= $k; $i++) {
        if (!is_array($value[$i])) {
          continue;
        }
        $dates[$i] = [
          'value' => $value[$i]['value'],
          'end_value' => $value[$i]['value2'],
          'duration' => (int) round(($value[$i]['value2'] - $value[$i]['value']) / 60),
          'rrule' => $rrule->id(),
          'rrule_index' => $ri,
          'timezone' => $timezone,
        ];
        $ri++;
      }
    }
    return $dates;
  }

  /**
   * Thanks to ivijan.stefan@gmail.com commenting at the page below.
   *
   * @see https://www.php.net/manual/en/function.date.php
   *
   * @throws \Exception
   */
  private function date($format = "r", $timestamp = FALSE, $timezone = FALSE): string {
    $userTimezone = new \DateTimeZone(!empty($timezone) ? $timezone : 'UTC');
    $gmtTimezone = new \DateTimeZone('UTC');
    $myDateTime = new \DateTime(($timestamp ? date("r", (int) $timestamp) : date("r")), $gmtTimezone);
    $offset = $userTimezone->getOffset($myDateTime);
    return date($format, ($timestamp ? (int) $timestamp : $myDateTime->format('U')) + $offset);
  }

}
