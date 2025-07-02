<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform date element.
 *
 * @group webform
 */
class WebformElementDateTest extends WebformElementBrowserTestBase {

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
  protected static $testWebforms = ['test_element_date'];

  /**
   * Test date element.
   */
  public function testDateElement() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_date');

    /* ********************************************************************** */
    // Render date elements.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_date');

    // Check '#format' values.
    $assert_session->fieldValueEquals('date_default', '2009-08-18');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assertIsString($form['elements']['date_default']['#default_value']);

    // Check date #max validation.
    $this->drupalGet('/webform/test_element_date');
    $edit = ['date_min_max' => '2010-08-18'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">date_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check date #min validation.
    $this->drupalGet('/webform/test_element_date');
    $edit = ['date_min_max' => '2006-08-18'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<em class="placeholder">date_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check dynamic date.
    $this->drupalGet('/webform/test_element_date');
    $min = \Drupal::service('date.formatter')->format(strtotime('-1 year'), 'html_date');
    $min_year = date('Y', strtotime('-1 year'));
    $max = \Drupal::service('date.formatter')->format(strtotime('+1 year'), 'html_date');
    $max_year = date('Y', strtotime('+1 year'));
    $default_value = \Drupal::service('date.formatter')->format(strtotime('now'), 'html_date');
    // @todo Remove once Drupal 10.0.x is only supported.
    if (floatval(\Drupal::VERSION) >= 10) {
      $assert_session->responseContains('<input min="' . $min . '" data-min-year="' . $min_year . '" max="' . $max . '" data-max-year="' . $max_year . '" type="date" data-drupal-selector="edit-date-min-max-dynamic" aria-describedby="edit-date-min-max-dynamic--description" id="edit-date-min-max-dynamic" name="date_min_max_dynamic" value="' . $default_value . '" class="form-date" />');
    }
    else {
      $assert_session->responseContains('<input min="' . $min . '" data-min-year="' . $min_year . '" max="' . $max . '" data-max-year="' . $max_year . '" type="date" data-drupal-selector="edit-date-min-max-dynamic" aria-describedby="edit-date-min-max-dynamic--description" data-drupal-date-format="Y-m-d" id="edit-date-min-max-dynamic" name="date_min_max_dynamic" value="' . $default_value . '" class="form-date" />');
    }

    /* ********************************************************************** */
    // Format date elements.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_date');
    $this->submitForm([], 'Preview');

    // Check date formats.
    $this->assertElementPreview('date_default', '2009-08-18');
    $this->assertElementPreview('date_custom', '18-Aug-2009');
    $this->assertElementPreview('date_min_max', '2009-08-18');
    $this->assertElementPreview('date_min_max_dynamic', date('Y-m-d', strtotime('now')));
  }

}
