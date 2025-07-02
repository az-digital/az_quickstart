<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform time element.
 *
 * @group webform
 */
class WebformElementTimeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_time'];

  /**
   * Test time element.
   */
  public function testTime() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_time');

    // Check time element.
    $assert_session->responseContains('<label for="edit-time-12-hour">time_12_hour</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-time-12-hour" data-webform-time-format="g:i A" type="time" id="edit-time-12-hour" name="time_12_hour" value="14:00" size="12" maxlength="12" class="form-time webform-time" />');

    // Check timepicker elements.
    $assert_session->responseContains('<input data-drupal-selector="edit-time-timepicker" data-webform-time-format="g:i A" type="text" id="edit-time-timepicker" name="time_timepicker" value="2:00 PM" size="12" maxlength="12" class="form-time webform-time" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-time-timepicker-min-max" aria-describedby="edit-time-timepicker-min-max--description" data-webform-time-format="g:i A" type="text" id="edit-time-timepicker-min-max" name="time_timepicker_min_max" value="2:00 PM" size="12" maxlength="12" min="14:00" max="18:00" class="form-time webform-time" />');
    $assert_session->responseContains('<input placeholder="{time}" data-drupal-selector="edit-time-timepicker-placeholder" data-webform-time-format="H:i" type="text" id="edit-time-timepicker-placeholder" name="time_timepicker_placeholder" value="" size="12" maxlength="12" class="form-time webform-time" />');

    // Check time processing.
    $this->drupalGet('/webform/test_element_time');
    $this->submitForm([], 'Submit');
    // phpcs:disable
    /*
    $time_12_hour_plus_6_hours = date('H:i:00', strtotime('+6 hours'));

    $assert_session->responseContains("time_default: '14:00:00'
time_24_hour: '14:00:00'
time_12_hour: '14:00:00'
time_12_hour_plus_6_hours: '$time_12_hour_plus_6_hours'
time_steps: '14:00:00'
time_min_max: '14:00:00'
time_timepicker: '14:00:00'
time_timepicker_min_max: '14:00:00'");
    */
    // phpcs:enable

    // Check time validation.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_24_hour' => 'not-valid'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">time_24_hour</em> must be a valid time.');

    // Check '0' string trigger validation error.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_default' => '0'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('em class="placeholder">time_default</em> must be a valid time.');

    // Check '++' string (faulty relative date) trigger validation error.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_default' => '++'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('em class="placeholder">time_default</em> must be a valid time.');

    // Check empty string trigger does not validation error.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_default' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<em class="placeholder">time_default</em> must be a valid time.');
    $assert_session->responseContains("time_default: ''");

    // Check time #max validation.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_min_max' => '12:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">time_min_max</em> must be on or after <em class="placeholder">14:00</em>.');

    // Check time #min validation.
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_min_max' => '22:00'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">time_min_max</em> must be on or before <em class="placeholder">18:00</em>.');

    // Check step trigger validation error (15 minutes step).
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_steps' => '14:16'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">time_steps</em> must be a valid time with intervals from the dropdown (<em class="placeholder">15</em> min/s).');

    // Check step validation (15 minutes step).
    $this->drupalGet('/webform/test_element_time');
    $edit = ['time_steps' => '14:15'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("time_steps: '14:15:00'");
  }

}
