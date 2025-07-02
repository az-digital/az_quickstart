<?php

declare(strict_types=1);

namespace Drupal\Tests\date_ap_style\Kernel;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\date_ap_style\ApStyleDateFormatter;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the ApStyleDateFormatter covering all scenarios.
 *
 * @group date_ap_style
 */
class ApStyleDateFormatterTest extends KernelTestBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The ApStyleDateFormatter instance.
   *
   * @var \Drupal\date_ap_style\ApStyleDateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'date_ap_style'];

  /**
   * Set up the test environment.
   */
  protected function setUp(): void {
    parent::setUp();

    // Retrieve the necessary services from the container.
    $this->languageManager = $this->container->get('language_manager');
    $this->configFactory = $this->container->get('config.factory');

    // Create the ApStyleDateFormatter instance using real services.
    $this->dateFormatter = new ApStyleDateFormatter($this->languageManager, $this->configFactory);
  }

  /**
   * Test out-of-the-box formatter.
   */
  public function testBasicFormat(): void {
    $options = [];
    $date = new DrupalDateTime('2017-06-22 00:00:00');
    $date = $date->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($date, $options);

    $this->assertEquals('June 22, 2017', $result);
  }

  /**
   * Test Appropriately Return Year.
   *
   * AP rules state when in the current year, don't show the year. So we need
   * to test a date in the current year that is also not the current month. Also
   * test date for current year that should not show date.
   */
  public function testDisplayYear(): void {
    $options = ['always_display_year' => TRUE];
    $currentDate = new DrupalDateTime();
    $currentYear = $currentDate->format('Y');
    $currentMonth = (int) $currentDate->format('m');

    // Initialize the test date.
    if ($currentMonth === 2) {
      $testDate = (clone $currentDate)->modify('+6 months');
    }
    else {
      $testDate = new DrupalDateTime("{$currentYear}-02-01");
    }

    // Get the timestamp for the test date.
    $timestamp = $testDate->getTimestamp();

    // Format the test date to "M. d, Y" (e.g., "Feb. 2, 2024").
    $formattedTestDateYear = $testDate->format('M. j, Y');
    $formattedTestDate = $testDate->format('M. j');

    // Format the test date for assertion or further testing.
    $show_year = $this->dateFormatter->formatTimestamp($timestamp, $options);
    $no_year = $this->dateFormatter->formatTimestamp($timestamp);

    // Example assertions for when to display year.
    $this->assertEquals($formattedTestDateYear, $show_year);
    $this->assertEquals($formattedTestDate, $no_year);
  }

  /**
   * Tests format for today.
   *
   * Tests use_today, cap_today and additionally with display_time.
   */
  public function testUseToday(): void {
    $options = ['use_today' => TRUE, 'cap_today' => TRUE];
    $today = new DrupalDateTime('today');
    $today->setTime(13, 30);
    $today = $today->getTimestamp();

    $result = $this->dateFormatter->formatTimestamp($today, $options);
    $this->assertEquals('Today', $result);

    $options = ['use_today' => TRUE, 'cap_today' => TRUE, 'display_time' => TRUE];
    $result = $this->dateFormatter->formatTimestamp($today, $options);
    $this->assertEquals('Today, 1:30 p.m.', $result);
  }

  /**
   * Test day of week with time for date in current week.
   */
  public function testUseDayOfWeek(): void {
    $options = ['display_day' => TRUE, 'display_time' => TRUE];
    $testDate = new DrupalDateTime();

    // Check if today is Friday.
    // 5 represents Friday.
    if ($testDate->format('N') == 5) {
      // If today is Friday, set the date to this Saturday.
      // Move to Saturday.
      $testDate->modify('+1 day');
    }
    $testDay = $testDate->format('l');
    $testDate->setTime(13, 0);
    $testDate = $testDate->getTimestamp();
    $testDay .= ', 1 p.m.';

    $result = $this->dateFormatter->formatTimestamp($testDate, $options);
    $this->assertEquals($testDay, $result);
  }

  /**
   * Tests format with month_only option enabled.
   */
  public function testMonthOnly(): void {
    $options = ['month_only' => TRUE];
    $date = new DrupalDateTime('2024-02-15 00:00:00');
    $date = $date->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($date, $options);
    $this->assertEquals('Feb.', $result);
  }

  /**
   * Tests hide date (show time only).
   */
  public function testHideDate(): void {
    $options = ['hide_date' => TRUE, 'display_time' => TRUE];
    $date = new DrupalDateTime('2024-08-22 17:21:00');
    $date = $date->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($date, $options);
    $this->assertEquals('5:21 p.m.', $result);
  }

  /**
   * Test time before date.
   */
  public function testTimeBeforeDate(): void {
    $options = ['time_before_date' => TRUE, 'display_time' => TRUE];
    $date = new DrupalDateTime('2012-01-10 12:00:00');
    $date = $date->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($date, $options);
    $this->assertEquals('12 p.m., Jan. 10, 2012', $result);
  }

  /**
   * Tests format with display_noon_and_midnight option enabled.
   */
  public function testDisplayNoonAndMidnight(): void {
    $options = [
      'display_time' => TRUE,
      'display_noon_and_midnight' => TRUE,
      'capitalize_noon_and_midnight' => TRUE,
    ];
    $start = new DrupalDateTime('2023-12-15 12:00:00');
    $start = $start->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($start, $options);
    $this->assertEquals('Dec. 15, 2023, Noon', $result);

    $start = new DrupalDateTime('2023-12-15 00:00:00');
    $start = $start->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($start, $options);
    $this->assertEquals('Dec. 15, 2023, Midnight', $result);
  }

  /**
   * Tests format with all day option enabled.
   */
  public function testDisplayAllDay(): void {
    $options = [
      'display_time' => TRUE,
      'use_all_day' => TRUE,
    ];

    $start = new DrupalDateTime('2023-12-15 00:00:00');
    $start = $start->getTimestamp();
    $result = $this->dateFormatter->formatTimestamp($start, $options);
    $this->assertEquals('Dec. 15, 2023, All Day', $result);
  }

  /**
   * Tests basic date range formatting.
   */
  public function testBasicDateRange(): void {
    $start = new DrupalDateTime('2023-12-15 00:00:00');
    $end = new DrupalDateTime('2023-12-20 00:00:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps);
    $this->assertEquals('Dec. 15 to 20, 2023', $result);
  }

  /**
   * Test month only in range when month and year is the same.
   */
  public function testMonthOnlySameMonthYear(): void {
    $options = ['month_only' => TRUE];
    $start = new DrupalDateTime('2024-09-01');
    $end = new DrupalDateTime('2024-09-30');

    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];

    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Sept.', $result);
  }

  /**
   * Tests when months are different and year is the same.
   */
  public function testMonthOnlyDifferentMonthsSameYear(): void {
    $options = ['month_only' => TRUE];
    $start = new DrupalDateTime('2024-09-01');
    $end = new DrupalDateTime('2024-10-01');

    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];

    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Sept. to Oct.', $result);
  }

  /**
   * Tests month only when years are different in range.
   */
  public function testMonthOnlyDifferentYears(): void {
    $options = ['month_only' => TRUE];
    $start = new DrupalDateTime('2022-12-01');
    $end = new DrupalDateTime('2023-01-01');

    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];

    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Dec. 2022 to Jan. 2023', $result);
  }

  /**
   * Tests date range with always_display_year enabled using same month.
   */
  public function testAlwaysDisplayYearRange(): void {
    $options = ['always_display_year' => TRUE];
    $currentDate = new DrupalDateTime('first day of this month');
    $currentYear = $currentDate->format('Y');
    $currentMonth = (int) $currentDate->format('m');

    // Initialize the test date.
    if ($currentMonth === 2) {
      $start = (clone $currentDate)->modify('+6 months');
    }
    else {
      $start = new DrupalDateTime("{$currentYear}-02-01");
    }
    $end = (clone $start)->modify('+1 week');

    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];

    $formatted = $start->format('M. j') . ' to ' . $end->format('j, Y');

    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals($formatted, $result);
  }

  /**
   * Tests range format with display_day option enabled.
   *
   * Provided that the start and end dates are the same (i.e., only the times
   * differ), AP style format implies that the day of the week should be
   * displayable, given the following logic:
   * - all references to Associated Press *dates* are treated as separate from
   *   references to Associated Press *times*
   * - On a date range where the start and end date are the same, the *date*
   *   part of the *date and time* would be considered a single value, and
   *   should display the day of the week.
   *
   * See issue: 3167278.
   */
  public function testDisplayDayTimeRange(): void {
    $options = ['display_day' => TRUE, 'display_time' => TRUE];
    $start = new DrupalDateTime();

    // Get the current day of the week (1 = Monday, 7 = Sunday)
    $currentDayOfWeek = $start->format('N');

    // If today is before Saturday, add one day to the current date.
    if ($currentDayOfWeek == 5) {
      $start->modify('-1 day');
    }
    $testDay = $start->format('l');
    $start->setTime(12, 0);
    $end = clone $start;
    $end->setTime(13, 0);
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps, $options);

    $this->assertStringContainsString($testDay, $result);
    $this->assertStringContainsString('12 p.m. to 1 p.m.', $result);
  }

  /**
   * Tests format for today.
   *
   * Tests use_today, cap_today and additionally with display_time.
   */
  public function testUseTodayRange() {
    $options = ['use_today' => TRUE, 'cap_today' => TRUE];
    $today = new DrupalDateTime('today');
    $today->setTime(11, 30);
    $end = (clone $today)->modify('+1 hour');
    $timestamps = [
      'start' => $today->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];

    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Today', $result);

    $options = ['use_today' => TRUE, 'cap_today' => TRUE, 'display_time' => TRUE];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Today, 11:30 a.m. to 12:30 p.m.', $result);
  }

  /**
   * Tests same month with different days.
   */
  public function testSameMonthDifferentDays() {
    $start = new DrupalDateTime('2023-09-09 00:00:00');
    $end = new DrupalDateTime('2023-09-20 00:00:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps);
    $this->assertEquals('Sept. 9 to 20, 2023', $result);
  }

  /**
   * Tests when the date is the same.
   */
  public function testSameDate() {
    $date = new DrupalDateTime('1981-05-07 00:00:00');
    $timestamps = [
      'start' => $date->getTimestamp(),
      'end' => $date->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps);
    $this->assertEquals('May 7, 1981', $result);
  }

  /**
   * Test the All Day output option on date ranges.
   */
  public function testDisplayAllDayRange() {
    $options = [
      'display_time' => TRUE,
      'use_all_day' => TRUE,
    ];

    $start = new DrupalDateTime('2023-12-15 00:00:00');
    $end = new DrupalDateTime('2023-12-15 11:59:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Dec. 15, 2023, All Day', $result);
  }

  /**
   * Tests format with display_time and time_before_date enabled.
   */
  public function testTimeBeforeDateRange() {
    $options = ['display_time' => TRUE, 'time_before_date' => TRUE];
    $start = new DrupalDateTime('2023-12-15 10:00:00');
    $end = new DrupalDateTime('2023-12-15 11:00:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('10 to 11 a.m., Dec. 15, 2023', $result);
  }

  /**
   * Tests format with hide_date and display_time enabled.
   */
  public function testHideDateRange() {
    $options = ['hide_date' => TRUE, 'display_time' => TRUE];
    $start = new DrupalDateTime('2024-12-15 10:00:00');
    $end = new DrupalDateTime('2024-12-15 11:00:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('10 to 11 a.m.', $result);
  }

  /**
   * Tests format with display_noon_and_midnight option enabled.
   */
  public function testDisplayNoonAndMidnightRange() {
    $options = [
      'display_time' => TRUE,
      'display_noon_and_midnight' => TRUE,
      'capitalize_noon_and_midnight' => TRUE,
    ];
    $start = new DrupalDateTime('2023-12-15 12:00:00');
    $start = $start->getTimestamp();
    $result = $this->dateFormatter->formatRange(['start' => $start, 'end' => $start], $options);
    $this->assertEquals('Dec. 15, 2023, Noon', $result);

    $start = new DrupalDateTime('2023-12-15 00:00:00');
    $start = $start->getTimestamp();
    $result = $this->dateFormatter->formatRange(['start' => $start, 'end' => $start], $options);
    $this->assertEquals('Dec. 15, 2023, Midnight', $result);
  }

  /**
   * Tests format with different separators.
   */
  public function testSeparatorHandling() {
    $options = ['separator' => 'endash'];
    $start = new DrupalDateTime('2012-01-10 00:00:00');
    $end = new DrupalDateTime('2012-01-20 00:00:00');
    $timestamps = [
      'start' => $start->getTimestamp(),
      'end' => $end->getTimestamp(),
    ];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Jan. 10 &ndash; 20, 2012', $result);

    $options = ['separator' => 'to'];
    $result = $this->dateFormatter->formatRange($timestamps, $options);
    $this->assertEquals('Jan. 10 to 20, 2012', $result);
  }

}
