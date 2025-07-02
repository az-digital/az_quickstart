<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform datetime element.
 *
 * @group webform
 */
class WebformElementDateTimeTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['jquery_ui_datepicker'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_datetime'];

  /**
   * Test datetime element.
   */
  public function testDateTime() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_datetime');

    // Check posted submission values.
    $this->postSubmission($webform);
    $assert_session->responseContains("datetime_default: '2009-08-18T16:00:00+1000'");
    $assert_session->responseContains("datetime_multiple:
  - '2009-08-18T16:00:00+1000'");
    $assert_session->responseContains("datetime_custom_composite:
  - datetime: '2009-08-18T16:00:00+1000'");

    $this->drupalGet('/webform/test_element_datetime');

    // Check datetime label has not for attributes.
    $assert_session->responseContains('<label>datetime_default</label>');

    // Check '#format' values.
    $assert_session->fieldValueEquals('datetime_default[date]', '2009-08-18');
    $assert_session->fieldValueEquals('datetime_default[time]', '16:00:00');

    // Check timepicker.
    $now_date = date('D, m/d/Y', strtotime('now'));

    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('        <input data-drupal-selector="edit-datetime-timepicker-date" type="text" min="Mon, 01/01/1900" max="Sat, 12/31/2050" placeholder="YYYY-MM-DD" data-help="Enter the date using the format YYYY-MM-DD (e.g., ' . $now_date . ')." id="edit-datetime-timepicker-date" name="datetime_timepicker[date]" value="Tue, 08/18/2009" size="15" class="form-text" />'),
      deprecatedCallable: fn() => $assert_session->responseContains('<input data-drupal-selector="edit-datetime-timepicker-date" title="Date (e.g. ' . $now_date . ')" type="text" min="Mon, 01/01/1900" max="Sat, 12/31/2050" placeholder="YYYY-MM-DD" data-help="Enter the date using the format YYYY-MM-DD (e.g., ' . $now_date . ')." id="edit-datetime-timepicker-date" name="datetime_timepicker[date]" value="Tue, 08/18/2009" size="15" class="form-text" />'),
    );

    $assert_session->responseContains('<input data-drupal-selector="edit-datetime-timepicker-time"');
    // Skip time which can change during the tests.
    // phpcs:ignore
    // $assert_session->responseContains('id="edit-datetime-timepicker-time" name="datetime_timepicker[time]" value="" size="12" maxlength="12" class="form-time webform-time" />');

    // Check date/time placeholder attribute.
    $assert_session->responseContains(' type="text" placeholder="{date}"');
    $assert_session->responseContains(' type="text" step="1" data-webform-time-format="H:i:s" placeholder="{time}"');

    // Check time with custom min/max/step attributes.
    $assert_session->responseContains('<input min="2009-01-01" data-min-year="2009" max="2009-12-31" data-max-year="2009" data-drupal-selector="edit-datetime-time-min-max-date"');
    $assert_session->responseContains('<input min="09:00:00" max="17:00:00" data-drupal-selector="edit-datetime-time-min-max-time"');
    $assert_session->responseContains('<input min="Thu, 01/01/2009" data-min-year="2009" max="Thu, 12/31/2009" data-max-year="2009" data-drupal-selector="edit-datetime-timepicker-time-min-max-date"');
    $assert_session->responseContains('<input min="09:00:00" max="17:00:00" data-drupal-selector="edit-datetime-timepicker-time-min-max-time"');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assertInstanceOf(DrupalDateTime::class, $form['elements']['datetime_default']['#default_value']);

    // Check datetime #date_date_max validation.
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_min_max[date]' => '2010-08-18'];
    $this->submitForm($edit, 'Submit');

    $assert_session->responseContains('<em class="placeholder">datetime_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datetime #date_date_min validation.
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_min_max[date]' => '2006-08-18'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datetime_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check datetime #date_max date validation.
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_min_max_time[date]' => '2009-12-31', 'datetime_min_max_time[time]' => '19:00:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datetime_min_max_time</em> must be on or before <em class="placeholder">2009-12-31 17:00:00</em>.');

    // Check datetime #date_min date validation.
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_min_max_time[date]' => '2009-01-01', 'datetime_min_max_time[time]' => '08:00:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datetime_min_max_time</em> must be on or after <em class="placeholder">2009-01-01 09:00:00</em>.');

    // Check: Issue #2723159: Datetime form element cannot validate when using a
    // format without seconds.
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);
    $assert_session->responseNotContains('The datetime_no_seconds date is invalid.');
    $this->assertEquals($submission->getElementData('datetime_no_seconds'), '2009-08-18T16:00:00+1000');

    // Check datetime #interval validation is displayed.
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_no_seconds[date]' => '2009-08-18', 'datetime_no_seconds[time]' => '00:01:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datetime_no_seconds: Time</em> must be a valid time with intervals from the dropdown (<em class="placeholder">15</em> min/s).');

    // Check datetime #interval validation is displayed via inline form errors.
    \Drupal::service('module_installer')->install(['inline_form_errors']);
    $this->drupalGet('/webform/test_element_datetime');
    $edit = ['datetime_no_seconds[date]' => '2009-08-18', 'datetime_no_seconds[time]' => '00:02:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datetime_no_seconds: Time</em> must be a valid time with intervals from the dropdown (<em class="placeholder">15</em> min/s).');
  }

}
