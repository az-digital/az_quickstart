<?php

namespace Drupal\Tests\webform\Functional\Form;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for form properties.
 *
 * @group webform
 */
class WebformFormPropertiesTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_properties', 'test_element_invalid'];

  /**
   * Test form properties.
   */
  public function testProperties() {
    global $base_path;

    $assert_session = $this->assertSession();

    // Check invalid elements .
    $this->drupalGet('/webform/test_element_invalid');
    $assert_session->responseContains('Unable to display this webform. Please contact the site administrator.');

    // Change invalid to empty elements.
    Webform::load('test_element_invalid')
      ->setElements([])
      ->save();

    // Check that exception message is still displayed.
    $this->drupalGet('/webform/test_element_invalid');
    $assert_session->responseContains('Unable to display this webform. Please contact the site administrator.');

    // Check that custom message is displayed to user who can update
    // the webform.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_element_invalid');
    $assert_session->responseNotContains('Unable to display this webform. Please contact the site administrator.');
    $assert_session->responseContains('This webform has no elements added to it.');
    $this->drupalLogout();

    // Check element's root properties moved to the webform's properties.
    $this->drupalGet('/webform/test_form_properties');
    $assert_session->responseMatches('/Form prefix<form /');
    $assert_session->responseMatches('/<\/form>\s+Form suffix/');
    $assert_session->responseContains('<form invalid="invalid" class="webform-submission-form webform-submission-add-form webform-submission-test-form-properties-form webform-submission-test-form-properties-add-form test-form-properties js-webform-details-toggle webform-details-toggle" style="border: 10px solid red; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-add-form" action="https://www.google.com/search" method="get" id="webform-submission-test-form-properties-add-form" accept-charset="UTF-8">');

    // Check editing webform settings style attributes and custom properties
    // updates the element's root properties.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_form_properties/settings/form');
    $edit = [
      'form_attributes[class][select][]' => ['form--inline clearfix', '_other_'],
      'form_attributes[class][other]' => 'test-form-properties',
      'form_attributes[style]' => 'border: 10px solid green; padding: 1em;',
      'form_attributes[attributes]' => '',
      'form_method' => '',
      'form_action' => '',
      'custom' => "'suffix': 'Form suffix TEST'
'prefix': 'Form prefix TEST'",
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/webform/test_form_properties');
    $assert_session->responseMatches('/Form prefix TEST<form /');
    $assert_session->responseMatches('/<\/form>\s+Form suffix TEST/');
    $assert_session->responseContains('<form class="webform-submission-form webform-submission-add-form webform-submission-test-form-properties-form webform-submission-test-form-properties-add-form form--inline clearfix test-form-properties js-webform-details-toggle webform-details-toggle" style="border: 10px solid green; padding: 1em;" data-drupal-selector="webform-submission-test-form-properties-add-form" action="' . $base_path . 'webform/test_form_properties" method="post" id="webform-submission-test-form-properties-add-form" accept-charset="UTF-8">');
  }

}
