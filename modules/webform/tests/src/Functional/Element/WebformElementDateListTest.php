<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform datelist element.
 *
 * @group webform
 */
class WebformElementDateListTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_datelist'];

  /**
   * Test datelist element.
   */
  public function testDateListElement() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_datelist');

    // Check posted submission values.
    $this->postSubmission($webform);
    $assert_session->responseContains("datelist_default: '2009-08-18T16:00:00+1000'
datelist_no_abbreviate: '2009-08-18T16:00:00+1000'
datelist_text_parts: '2009-08-18T16:00:00+1000'
datelist_datetime: '2009-08-18T16:00:00+1000'
datelist_date: '2009-08-18T00:00:00+1000'
datelist_min_max: '2009-08-18T00:00:00+1000'
datelist_min_max_time: '2009-01-01T09:00:00+1100'
datelist_date_year_range_reverse: ''
datelist_required_error: '2009-08-18T16:00:00+1000'
datelist_conditional: 0
datelist_conditional_required: ''
datelist_multiple:
  - '2009-08-18T16:00:00+1000'
datelist_custom_composite:
  - datelist: '2009-08-18T16:00:00+1000'");

    $this->drupalGet('/webform/test_element_datelist');

    // Check datelist label has not for attributes.
    $assert_session->responseContains('<label>datelist_default</label>');

    // Check '#format' values.
    $assert_session->fieldValueEquals('datelist_default[month]', '8');

    // Check '#date_abbreviate': false.
    $assert_session->responseContains('<select data-drupal-selector="edit-datelist-no-abbreviate-month" title="Month" id="edit-datelist-no-abbreviate-month" name="datelist_no_abbreviate[month]" class="form-select"><option value="">Month</option><option value="1">January</option>');

    // Check date year range reverse.
    $this->drupalGet('/webform/test_element_datelist');
    $assert_session->responseContains('<select data-drupal-selector="edit-datelist-date-year-range-reverse-year" title="Year" id="edit-datelist-date-year-range-reverse-year" name="datelist_date_year_range_reverse[year]" class="form-select"><option value="" selected="selected">Year</option><option value="2010">2010</option><option value="2009">2009</option><option value="2008">2008</option><option value="2007">2007</option><option value="2006">2006</option><option value="2005">2005</option></select>');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assertInstanceOf(DrupalDateTime::class, $form['elements']['datelist_default']['#default_value']);

    // Check datelist #date_date_max validation.
    $this->drupalGet('/webform/test_element_datelist');
    $edit = [
      'datelist_min_max[year]' => '2010',
      'datelist_min_max[month]' => '8',
      'datelist_min_max[day]' => '18',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datelist_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check datelist #date_date_min validation.
    $edit = [
      'datelist_min_max[year]' => '2006',
      'datelist_min_max[month]' => '8',
      'datelist_min_max[day]' => '18',
    ];
    $this->drupalGet('/webform/test_element_datelist');
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datelist_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check datelist #date_max validation.
    $this->drupalGet('/webform/test_element_datelist');
    $edit = [
      'datelist_min_max_time[year]' => '2009',
      'datelist_min_max_time[month]' => '12',
      'datelist_min_max_time[day]' => '31',
      'datelist_min_max_time[hour]' => '18',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datelist_min_max_time</em> must be on or before <em class="placeholder">2009-12-31 17:00:00</em>.');

    // Check datelist #date_min validation.
    $this->drupalGet('/webform/test_element_datelist');
    $edit = [
      'datelist_min_max_time[year]' => '2009',
      'datelist_min_max_time[month]' => '1',
      'datelist_min_max_time[day]' => '1',
      'datelist_min_max_time[hour]' => '8',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">datelist_min_max_time</em> must be on or after <em class="placeholder">2009-01-01 09:00:00</em>.');

    // Check custom required error.
    $this->drupalGet('/webform/test_element_datelist');
    $edit = [
      'datelist_required_error[year]' => '',
      'datelist_required_error[month]' => '',
      'datelist_required_error[day]' => '',
      'datelist_required_error[hour]' => '',
      'datelist_required_error[minute]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Custom required error');

    // Check that the datelist element's states is copied to the child inputs.
    $this->drupalGet('/webform/test_element_datelist');
    $assert_session->responseContains('<select data-drupal-selector="edit-datelist-conditional-required-year" title="Year" id="edit-datelist-conditional-required-year" name="datelist_conditional_required[year]" class="form-select" data-drupal-states="{&quot;required&quot;:{&quot;.webform-submission-test-element-datelist-add-form :input[name=\u0022datelist_conditional\u0022]&quot;:{&quot;checked&quot;:true}}}">');
  }

}
